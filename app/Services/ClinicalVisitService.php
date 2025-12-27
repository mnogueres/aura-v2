<?php

namespace App\Services;

use App\Events\Clinical\VisitRecorded;
use App\Events\Clinical\TreatmentRecorded;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * ClinicalVisitService - Business logic for clinical visits.
 *
 * IMPORTANT: Creating treatments inline within createVisit() is a convenience method
 * for FASE 20.1 ONLY. This is NOT the canonical flow.
 *
 * FASE 20.6: Complete visit lifecycle management.
 * Visits can be updated or removed with domain validations.
 *
 * Emits:
 * - clinical.visit.recorded
 * - clinical.visit.updated
 * - clinical.visit.removed
 * - clinical.treatment.recorded (if treatments provided in createVisit)
 */
class ClinicalVisitService
{
    public function __construct(
        private readonly EventService $eventService
    ) {}

    /**
     * Create a clinical visit with optional treatments.
     *
     * NOTE: Inline treatment creation is a temporary convenience.
     * Canonical flow will be: createVisit() → AddTreatmentToVisit() (separate calls).
     *
     * @param array $visitData ['clinic_id', 'patient_id', 'professional_id', 'occurred_at', 'visit_type', 'summary']
     * @param array $treatments [['type', 'tooth', 'amount', 'notes'], ...] (optional, NOT canonical)
     * @return Visit
     * @throws \DomainException
     */
    public function createVisit(array $visitData, array $treatments = []): Visit
    {
        // Domain validations
        $this->validateVisitData($visitData);

        return DB::transaction(function () use ($visitData, $treatments) {
            // 1. Create visit (write model)
            $visit = Visit::create($visitData);

            // 2. Emit clinical.visit.recorded
            $this->eventService->emit(
                new VisitRecorded(
                    clinic_id: $visit->clinic_id,
                    visit_id: $visit->id,
                    patient_id: $visit->patient_id,
                    professional_id: $visit->professional_id,
                    occurred_at: $visit->occurred_at->toIso8601String(),
                    visit_type: $visit->visit_type,
                    summary: $visit->summary
                )
            );

            // 3. Create treatments (if any) - NOT CANONICAL FLOW
            // Future: Use AddTreatmentToVisit command instead
            foreach ($treatments as $treatmentData) {
                $treatment = $visit->treatments()->create([
                    ...$treatmentData,
                    'clinic_id' => $visit->clinic_id,
                    'patient_id' => $visit->patient_id,
                ]);

                // 4. Emit clinical.treatment.recorded per treatment
                $this->eventService->emit(
                    new TreatmentRecorded(
                        clinic_id: $visit->clinic_id,
                        treatment_id: $treatment->id,
                        visit_id: $visit->id,
                        patient_id: $visit->patient_id,
                        type: $treatment->type,
                        tooth: $treatment->tooth,
                        amount: $treatment->amount,
                        notes: $treatment->notes
                    )
                );
            }

            return $visit;
        });
    }

    /**
     * Update an existing visit (CANONICAL flow - FASE 20.6).
     *
     * @param string $visitId
     * @param array $updates ['occurred_at', 'visit_type', 'summary', 'professional_id'] - only provided fields are updated
     * @return Visit
     * @throws \DomainException
     */
    public function updateVisit(string $visitId, array $updates): Visit
    {
        // Validate updates data
        $this->validateVisitUpdates($updates);

        // Find visit (include soft-deleted to give proper error message)
        $visit = Visit::withTrashed()->find($visitId);

        if (!$visit) {
            throw new \DomainException('Visit not found');
        }

        // Check not soft-deleted
        if ($visit->trashed()) {
            throw new \DomainException('Cannot update deleted visit');
        }

        return DB::transaction(function () use ($visit, $updates) {
            // Update only provided fields
            $visit->update(array_filter($updates, fn($value, $key) =>
                array_key_exists($key, $updates), ARRAY_FILTER_USE_BOTH
            ));

            // Reload to get fresh state
            $visit->refresh();

            // Emit clinical.visit.updated with complete POST-update state
            $this->eventService->emit(
                new \App\Events\Clinical\VisitUpdated(
                    clinic_id: $visit->clinic_id,
                    visit_id: $visit->id,
                    patient_id: $visit->patient_id,
                    occurred_at: $visit->occurred_at->toIso8601String(),
                    visit_type: $visit->visit_type,
                    summary: $visit->summary,
                    professional_id: $visit->professional_id
                )
            );

            return $visit;
        });
    }

    /**
     * Remove a visit (CANONICAL flow - FASE 20.6).
     *
     * CRITICAL RULE: Cannot remove a visit that has treatments.
     * Soft deletes the visit in write model.
     * Projector will hard delete from read model.
     *
     * @param string $visitId
     * @return void
     * @throws \DomainException
     */
    public function removeVisit(string $visitId): void
    {
        $visit = Visit::withTrashed()->find($visitId);

        if (!$visit) {
            throw new \DomainException('Visit not found');
        }

        if ($visit->trashed()) {
            throw new \DomainException('Visit already deleted');
        }

        // CRITICAL: Check if visit has treatments
        $treatmentsCount = $visit->treatments()->count();
        if ($treatmentsCount > 0) {
            throw new \DomainException('Cannot remove visit with associated treatments');
        }

        DB::transaction(function () use ($visit) {
            // Capture data before soft delete
            $clinic_id = $visit->clinic_id;
            $visit_id = $visit->id;
            $patient_id = $visit->patient_id;

            // Soft delete (write model)
            $visit->delete();

            // Emit clinical.visit.removed
            $this->eventService->emit(
                new \App\Events\Clinical\VisitRemoved(
                    clinic_id: $clinic_id,
                    visit_id: $visit_id,
                    patient_id: $patient_id
                )
            );
        });
    }

    /**
     * Validate visit data (domain rules).
     *
     * @param array $visitData
     * @return void
     * @throws \DomainException
     */
    private function validateVisitData(array $visitData): void
    {
        if (empty($visitData['clinic_id'])) {
            throw new \DomainException('clinic_id is required');
        }

        if (empty($visitData['patient_id'])) {
            throw new \DomainException('patient_id is required');
        }

        if (empty($visitData['occurred_at'])) {
            throw new \DomainException('occurred_at is required');
        }

        // Validate occurred_at is a valid date
        try {
            if (is_string($visitData['occurred_at'])) {
                new \DateTime($visitData['occurred_at']);
            } elseif (!($visitData['occurred_at'] instanceof \DateTimeInterface)) {
                throw new \DomainException('occurred_at must be a valid date');
            }
        } catch (\Exception $e) {
            throw new \DomainException('occurred_at must be a valid date: ' . $e->getMessage());
        }
    }

    /**
     * Validate visit updates (domain rules).
     *
     * @param array $updates
     * @return void
     * @throws \DomainException
     */
    private function validateVisitUpdates(array $updates): void
    {
        // If occurred_at is being updated, it must be a valid date
        if (array_key_exists('occurred_at', $updates) && !empty($updates['occurred_at'])) {
            try {
                if (is_string($updates['occurred_at'])) {
                    new \DateTime($updates['occurred_at']);
                } elseif (!($updates['occurred_at'] instanceof \DateTimeInterface)) {
                    throw new \DomainException('occurred_at must be a valid date');
                }
            } catch (\Exception $e) {
                throw new \DomainException('occurred_at must be a valid date: ' . $e->getMessage());
            }
        }
    }
}
