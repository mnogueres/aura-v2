<?php

namespace App\Services;

use App\Events\Clinical\TreatmentAdded;
use App\Models\Visit;
use App\Models\VisitTreatment;
use Illuminate\Support\Facades\DB;

/**
 * ClinicalTreatmentService - Business logic for clinical treatments.
 *
 * FASE 20.3-20.4: Canonical flow for treatment lifecycle management.
 * Treatments are independent entities that can be added, updated, and removed
 * from visits after the visit has been created.
 *
 * Emits:
 * - clinical.treatment.added
 * - clinical.treatment.updated
 * - clinical.treatment.removed
 */
class ClinicalTreatmentService
{
    public function __construct(
        private readonly EventService $eventService
    ) {}

    /**
     * Add a treatment to an existing visit (CANONICAL flow).
     *
     * @param string $visitId
     * @param array $treatmentData ['type' (required), 'tooth', 'amount', 'notes']
     * @return VisitTreatment
     * @throws \DomainException
     */
    public function addTreatmentToVisit(string $visitId, array $treatmentData): VisitTreatment
    {
        // Domain validations
        $this->validateTreatmentData($treatmentData);

        // Validate visit exists
        $visit = Visit::find($visitId);

        if (!$visit) {
            throw new \DomainException('Visit not found');
        }

        return DB::transaction(function () use ($visit, $treatmentData) {
            // 1. Create treatment (write model)
            $treatment = VisitTreatment::create([
                'visit_id' => $visit->id,
                'clinic_id' => $visit->clinic_id,
                'patient_id' => $visit->patient_id,
                'type' => $treatmentData['type'],
                'tooth' => $treatmentData['tooth'] ?? null,
                'amount' => $treatmentData['amount'] ?? null,
                'notes' => $treatmentData['notes'] ?? null,
            ]);

            // 2. Emit clinical.treatment.added
            $this->eventService->emit(
                new TreatmentAdded(
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

            return $treatment;
        });
    }

    /**
     * Update an existing treatment (CANONICAL flow - FASE 20.4).
     *
     * @param string $treatmentId
     * @param array $updates ['type', 'tooth', 'amount', 'notes'] - only provided fields are updated
     * @return VisitTreatment
     * @throws \DomainException
     */
    public function updateTreatment(string $treatmentId, array $updates): VisitTreatment
    {
        // Validate updates data
        $this->validateTreatmentUpdates($updates);

        // Find treatment (include soft-deleted to give proper error message)
        $treatment = VisitTreatment::withTrashed()->find($treatmentId);

        if (!$treatment) {
            throw new \DomainException('Treatment not found');
        }

        // Check not soft-deleted
        if ($treatment->trashed()) {
            throw new \DomainException('Cannot update deleted treatment');
        }

        return DB::transaction(function () use ($treatment, $updates) {
            // Update only provided fields
            $treatment->update(array_filter($updates, fn($value) => $value !== null || array_key_exists($value, $updates)));

            // Reload to get fresh state
            $treatment->refresh();

            // Emit clinical.treatment.updated with complete POST-update state
            $this->eventService->emit(
                new \App\Events\Clinical\TreatmentUpdated(
                    clinic_id: $treatment->clinic_id,
                    treatment_id: $treatment->id,
                    visit_id: $treatment->visit_id,
                    patient_id: $treatment->patient_id,
                    type: $treatment->type,
                    tooth: $treatment->tooth,
                    amount: $treatment->amount,
                    notes: $treatment->notes
                )
            );

            return $treatment;
        });
    }

    /**
     * Remove a treatment from a visit (CANONICAL flow - FASE 20.4).
     *
     * Soft deletes the treatment in write model.
     * Projector will hard delete from read model and decrement count.
     *
     * @param string $treatmentId
     * @return void
     * @throws \DomainException
     */
    public function removeTreatmentFromVisit(string $treatmentId): void
    {
        $treatment = VisitTreatment::withTrashed()->find($treatmentId);

        if (!$treatment) {
            throw new \DomainException('Treatment not found');
        }

        if ($treatment->trashed()) {
            throw new \DomainException('Treatment already deleted');
        }

        DB::transaction(function () use ($treatment) {
            // Capture data before soft delete
            $clinic_id = $treatment->clinic_id;
            $visit_id = $treatment->visit_id;
            $patient_id = $treatment->patient_id;
            $treatment_id = $treatment->id;

            // Soft delete (write model)
            $treatment->delete();

            // Emit clinical.treatment.removed
            $this->eventService->emit(
                new \App\Events\Clinical\TreatmentRemoved(
                    clinic_id: $clinic_id,
                    treatment_id: $treatment_id,
                    visit_id: $visit_id,
                    patient_id: $patient_id
                )
            );
        });
    }

    /**
     * Validate treatment data (domain rules).
     *
     * @param array $treatmentData
     * @return void
     * @throws \DomainException
     */
    private function validateTreatmentData(array $treatmentData): void
    {
        if (empty($treatmentData['type'])) {
            throw new \DomainException('type is required');
        }

        // Validate amount is numeric and positive if present
        if (isset($treatmentData['amount'])) {
            if (!is_numeric($treatmentData['amount'])) {
                throw new \DomainException('amount must be numeric');
            }

            if ($treatmentData['amount'] < 0) {
                throw new \DomainException('amount must be positive');
            }
        }
    }

    /**
     * Validate treatment updates (domain rules).
     *
     * @param array $updates
     * @return void
     * @throws \DomainException
     */
    private function validateTreatmentUpdates(array $updates): void
    {
        // If type is being updated, it cannot be empty
        if (array_key_exists('type', $updates) && empty($updates['type'])) {
            throw new \DomainException('type cannot be empty');
        }

        // Validate amount if provided
        if (array_key_exists('amount', $updates) && $updates['amount'] !== null) {
            if (!is_numeric($updates['amount'])) {
                throw new \DomainException('amount must be numeric');
            }

            if ($updates['amount'] < 0) {
                throw new \DomainException('amount must be positive or zero');
            }
        }
    }
}
