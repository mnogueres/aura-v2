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
     * Dispatch the event to Laravel's event system.
     *
     * IMPORTANT: This re-dispatches events that were already emitted.
     * Listeners must be idempotent and NOT create new outbox entries.
     *
     * @param EventOutbox $outboxEvent
     * @return void
     */
    private function dispatchEvent(EventOutbox $outboxEvent): void
    {
        // For now, we just log that we would dispatch the event
        // In a real implementation, you would rehydrate the event class
        // and dispatch it to listeners

        // Example of what could be done:
        // $eventClass = $this->getEventClassForName($outboxEvent->event_name);
        // $event = $this->rehydrateEvent($eventClass, $outboxEvent->payload);
        // event($event);

        // For this phase, we just log it as processed
        Log::channel('api')->debug('Event dispatched internally', [
            'outbox_id' => $outboxEvent->id,
            'event_name' => $outboxEvent->event_name,
            'payload' => $outboxEvent->payload,
        ]);
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
