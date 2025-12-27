<?php

namespace App\Services;

use App\Models\EventOutbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OutboxEventConsumer - Internal worker for processing outbox events.
 *
 * Responsibilities:
 * - Read pending events from outbox
 * - Process events with idempotency guarantees
 * - Mark events as processed or failed
 * - Handle retries with backoff
 *
 * This is INFRASTRUCTURE-ONLY. No domain logic here.
 */
class OutboxEventConsumer
{
    private const MAX_ATTEMPTS = 5;
    private const BATCH_SIZE = 10;

    /**
     * Process pending events from the outbox.
     *
     * @param int|null $batchSize Number of events to process (null = use default)
     * @return array Processing statistics
     */
    public function processPendingEvents(?int $batchSize = null): array
    {
        $batchSize = $batchSize ?? self::BATCH_SIZE;
        $processed = 0;
        $failed = 0;
        $skipped = 0;

        // Get pending events ordered by occurred_at
        $events = EventOutbox::pending()
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('occurred_at')
            ->limit($batchSize)
            ->get();

        foreach ($events as $event) {
            $result = $this->processEvent($event);

            match ($result) {
                'processed' => $processed++,
                'failed' => $failed++,
                'skipped' => $skipped++,
            };
        }

        Log::channel('api')->info('Outbox batch processed', [
            'batch_size' => $batchSize,
            'processed' => $processed,
            'failed' => $failed,
            'skipped' => $skipped,
        ]);

        return [
            'processed' => $processed,
            'failed' => $failed,
            'skipped' => $skipped,
            'total' => $events->count(),
        ];
    }

    /**
     * Process a single event with idempotency guarantees.
     *
     * @param EventOutbox $event
     * @return string 'processed', 'failed', or 'skipped'
     */
    private function processEvent(EventOutbox $event): string
    {
        // Use database lock to ensure idempotency
        return DB::transaction(function () use ($event) {
            // Lock the record for update to prevent concurrent processing
            $lockedEvent = EventOutbox::where('id', $event->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            // If event was already processed by another worker, skip
            if (!$lockedEvent) {
                Log::channel('api')->debug('Event already processed or locked', [
                    'outbox_id' => $event->id,
                    'event_name' => $event->event_name,
                ]);

                return 'skipped';
            }

            try {
                // Rehydrate and dispatch the event
                $this->dispatchEvent($lockedEvent);

                // Mark as processed
                $lockedEvent->markAsProcessed();

                Log::channel('api')->info('Outbox event processed', [
                    'outbox_id' => $lockedEvent->id,
                    'event_name' => $lockedEvent->event_name,
                    'clinic_id' => $lockedEvent->clinic_id,
                    'attempt' => $lockedEvent->attempts + 1,
                ]);

                return 'processed';
            } catch (\Throwable $e) {
                // Increment attempts and record error
                $lockedEvent->incrementAttempts();

                // If max attempts reached, mark as failed
                if ($lockedEvent->attempts >= self::MAX_ATTEMPTS) {
                    $lockedEvent->markAsFailed($e->getMessage());

                    Log::channel('api')->error('Outbox event failed permanently', [
                        'outbox_id' => $lockedEvent->id,
                        'event_name' => $lockedEvent->event_name,
                        'clinic_id' => $lockedEvent->clinic_id,
                        'attempts' => $lockedEvent->attempts,
                        'error' => $e->getMessage(),
                    ]);
                } else {
                    // Update error but keep as pending for retry
                    $lockedEvent->update([
                        'last_error' => $e->getMessage(),
                    ]);

                    Log::channel('api')->warning('Outbox event failed, will retry', [
                        'outbox_id' => $lockedEvent->id,
                        'event_name' => $lockedEvent->event_name,
                        'clinic_id' => $lockedEvent->clinic_id,
                        'attempt' => $lockedEvent->attempts,
                        'error' => $e->getMessage(),
                    ]);
                }

                return 'failed';
            }
        });
    }

    /**
     * Dispatch the event to projectors.
     *
     * IMPORTANT: This re-dispatches events that were already emitted.
     * Projectors must be idempotent and NOT create new outbox entries.
     *
     * @param EventOutbox $outboxEvent
     * @return void
     */
    private function dispatchEvent(EventOutbox $outboxEvent): void
    {
        $eventName = $outboxEvent->event_name;
        $payload = $outboxEvent->payload;

        Log::channel('api')->debug('Dispatching event to projectors', [
            'outbox_id' => $outboxEvent->id,
            'event_name' => $eventName,
        ]);

        // Route event to appropriate projector
        match($eventName) {
            'clinical.visit.recorded' => $this->dispatchToVisitProjector($outboxEvent),
            'clinical.visit.updated' => $this->dispatchToVisitUpdatedProjector($outboxEvent),
            'clinical.visit.removed' => $this->dispatchToVisitRemovedProjector($outboxEvent),
            'clinical.treatment.recorded' => $this->dispatchToTreatmentProjector($outboxEvent),
            'clinical.treatment.added' => $this->dispatchToTreatmentAddedProjector($outboxEvent),
            'clinical.treatment.updated' => $this->dispatchToTreatmentUpdatedProjector($outboxEvent),
            'clinical.treatment.removed' => $this->dispatchToTreatmentRemovedProjector($outboxEvent),
            'billing.invoice.created', 'billing.invoice.issued', 'billing.invoice.paid' =>
                $this->dispatchToBillingProjectors($outboxEvent),
            'billing.payment.recorded', 'billing.payment.applied', 'billing.payment.unlinked' =>
                $this->dispatchToBillingProjectors($outboxEvent),
            'crm.patient.created' => $this->dispatchToPatientProjectors($outboxEvent),
            'platform.rate_limited', 'platform.idempotency.replayed', 'platform.idempotency.conflict' =>
                $this->dispatchToAuditProjector($outboxEvent),
            default => Log::channel('api')->warning('No projector mapped for event', [
                'event_name' => $eventName,
                'outbox_id' => $outboxEvent->id,
            ]),
        };
    }

    /**
     * Dispatch clinical.visit.recorded to projector.
     */
    private function dispatchToVisitProjector(EventOutbox $outboxEvent): void
    {
        $event = $this->rehydrateVisitRecorded($outboxEvent);
        app(\App\Projections\ClinicalVisitProjector::class)->handleVisitRecorded($event);
    }

    /**
     * Dispatch clinical.visit.updated to projector (CANONICAL flow - FASE 20.6).
     */
    private function dispatchToVisitUpdatedProjector(EventOutbox $outboxEvent): void
    {
        $event = $this->rehydrateVisitUpdated($outboxEvent);
        app(\App\Projections\ClinicalVisitProjector::class)->handleVisitUpdated($event);
    }

    /**
     * Dispatch clinical.visit.removed to projector (CANONICAL flow - FASE 20.6).
     */
    private function dispatchToVisitRemovedProjector(EventOutbox $outboxEvent): void
    {
        $event = $this->rehydrateVisitRemoved($outboxEvent);
        app(\App\Projections\ClinicalVisitProjector::class)->handleVisitRemoved($event);
    }

    /**
     * Dispatch clinical.treatment.recorded to projector.
     */
    private function dispatchToTreatmentProjector(EventOutbox $outboxEvent): void
    {
        $event = $this->rehydrateTreatmentRecorded($outboxEvent);
        app(\App\Projections\ClinicalTreatmentProjector::class)->handleTreatmentRecorded($event);
    }

    /**
     * Dispatch clinical.treatment.added to projector (CANONICAL flow - FASE 20.3).
     */
    private function dispatchToTreatmentAddedProjector(EventOutbox $outboxEvent): void
    {
        $event = $this->rehydrateTreatmentAdded($outboxEvent);
        app(\App\Projections\ClinicalTreatmentProjector::class)->handleTreatmentAdded($event);
    }

    /**
     * Dispatch clinical.treatment.updated to projector (CANONICAL flow - FASE 20.4).
     */
    private function dispatchToTreatmentUpdatedProjector(EventOutbox $outboxEvent): void
    {
        $event = $this->rehydrateTreatmentUpdated($outboxEvent);
        app(\App\Projections\ClinicalTreatmentProjector::class)->handleTreatmentUpdated($event);
    }

    /**
     * Dispatch clinical.treatment.removed to projector (CANONICAL flow - FASE 20.4).
     */
    private function dispatchToTreatmentRemovedProjector(EventOutbox $outboxEvent): void
    {
        $event = $this->rehydrateTreatmentRemoved($outboxEvent);
        app(\App\Projections\ClinicalTreatmentProjector::class)->handleTreatmentRemoved($event);
    }

    /**
     * Dispatch billing events to projectors (stub for now).
     */
    private function dispatchToBillingProjectors(EventOutbox $outboxEvent): void
    {
        // Billing projectors implementation pending
        Log::channel('api')->debug('Billing projector dispatch (stub)', [
            'event_name' => $outboxEvent->event_name,
        ]);
    }

    /**
     * Dispatch patient events to projectors (stub for now).
     */
    private function dispatchToPatientProjectors(EventOutbox $outboxEvent): void
    {
        // Patient projectors implementation pending
        Log::channel('api')->debug('Patient projector dispatch (stub)', [
            'event_name' => $outboxEvent->event_name,
        ]);
    }

    /**
     * Dispatch platform events to audit projector (stub for now).
     */
    private function dispatchToAuditProjector(EventOutbox $outboxEvent): void
    {
        // Audit projector implementation pending
        Log::channel('api')->debug('Audit projector dispatch (stub)', [
            'event_name' => $outboxEvent->event_name,
        ]);
    }

    /**
     * Rehydrate VisitRecorded event from outbox.
     */
    private function rehydrateVisitRecorded(EventOutbox $outboxEvent): \App\Events\Clinical\VisitRecorded
    {
        $p = $outboxEvent->payload;

        return new \App\Events\Clinical\VisitRecorded(
            clinic_id: $p['clinic_id'],
            visit_id: $p['visit_id'],
            patient_id: $p['patient_id'],
            professional_id: $p['professional_id'] ?? null,
            occurred_at: $p['occurred_at'],
            visit_type: $p['visit_type'] ?? null,
            summary: $p['summary'] ?? null,
            request_id: $outboxEvent->request_id,
            user_id: $outboxEvent->user_id
        );
    }

    /**
     * Rehydrate VisitUpdated event from outbox (CANONICAL flow - FASE 20.6).
     */
    private function rehydrateVisitUpdated(EventOutbox $outboxEvent): \App\Events\Clinical\VisitUpdated
    {
        $p = $outboxEvent->payload;

        return new \App\Events\Clinical\VisitUpdated(
            clinic_id: $p['clinic_id'],
            visit_id: $p['visit_id'],
            patient_id: $p['patient_id'],
            occurred_at: $p['occurred_at'],
            visit_type: $p['visit_type'] ?? null,
            summary: $p['summary'] ?? null,
            professional_id: $p['professional_id'] ?? null,
            request_id: $outboxEvent->request_id,
            user_id: $outboxEvent->user_id
        );
    }

    /**
     * Rehydrate VisitRemoved event from outbox (CANONICAL flow - FASE 20.6).
     */
    private function rehydrateVisitRemoved(EventOutbox $outboxEvent): \App\Events\Clinical\VisitRemoved
    {
        $p = $outboxEvent->payload;

        return new \App\Events\Clinical\VisitRemoved(
            clinic_id: $p['clinic_id'],
            visit_id: $p['visit_id'],
            patient_id: $p['patient_id'],
            request_id: $outboxEvent->request_id,
            user_id: $outboxEvent->user_id
        );
    }

    /**
     * Rehydrate TreatmentRecorded event from outbox.
     */
    private function rehydrateTreatmentRecorded(EventOutbox $outboxEvent): \App\Events\Clinical\TreatmentRecorded
    {
        $p = $outboxEvent->payload;

        return new \App\Events\Clinical\TreatmentRecorded(
            clinic_id: $p['clinic_id'],
            treatment_id: $p['treatment_id'],
            visit_id: $p['visit_id'],
            patient_id: $p['patient_id'],
            type: $p['type'],
            tooth: $p['tooth'] ?? null,
            amount: $p['amount'] ?? null,
            notes: $p['notes'] ?? null,
            request_id: $outboxEvent->request_id,
            user_id: $outboxEvent->user_id
        );
    }

    /**
     * Rehydrate TreatmentAdded event from outbox (CANONICAL flow - FASE 20.3).
     */
    private function rehydrateTreatmentAdded(EventOutbox $outboxEvent): \App\Events\Clinical\TreatmentAdded
    {
        $p = $outboxEvent->payload;

        return new \App\Events\Clinical\TreatmentAdded(
            clinic_id: $p['clinic_id'],
            treatment_id: $p['treatment_id'],
            visit_id: $p['visit_id'],
            patient_id: $p['patient_id'],
            type: $p['type'],
            tooth: $p['tooth'] ?? null,
            amount: $p['amount'] ?? null,
            notes: $p['notes'] ?? null,
            request_id: $outboxEvent->request_id,
            user_id: $outboxEvent->user_id
        );
    }

    /**
     * Rehydrate TreatmentUpdated event from outbox (CANONICAL flow - FASE 20.4).
     */
    private function rehydrateTreatmentUpdated(EventOutbox $outboxEvent): \App\Events\Clinical\TreatmentUpdated
    {
        $p = $outboxEvent->payload;

        return new \App\Events\Clinical\TreatmentUpdated(
            clinic_id: $p['clinic_id'],
            treatment_id: $p['treatment_id'],
            visit_id: $p['visit_id'],
            patient_id: $p['patient_id'],
            type: $p['type'],
            tooth: $p['tooth'] ?? null,
            amount: $p['amount'] ?? null,
            notes: $p['notes'] ?? null,
            request_id: $outboxEvent->request_id,
            user_id: $outboxEvent->user_id
        );
    }

    /**
     * Rehydrate TreatmentRemoved event from outbox (CANONICAL flow - FASE 20.4).
     */
    private function rehydrateTreatmentRemoved(EventOutbox $outboxEvent): \App\Events\Clinical\TreatmentRemoved
    {
        $p = $outboxEvent->payload;

        return new \App\Events\Clinical\TreatmentRemoved(
            clinic_id: $p['clinic_id'],
            treatment_id: $p['treatment_id'],
            visit_id: $p['visit_id'],
            patient_id: $p['patient_id'],
            request_id: $outboxEvent->request_id,
            user_id: $outboxEvent->user_id
        );
    }

    /**
     * Get count of pending events.
     *
     * @return int
     */
    public function getPendingCount(): int
    {
        return EventOutbox::pending()
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->count();
    }

    /**
     * Get count of failed events.
     *
     * @return int
     */
    public function getFailedCount(): int
    {
        return EventOutbox::failed()->count();
    }
}
