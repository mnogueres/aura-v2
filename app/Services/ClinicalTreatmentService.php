<?php

namespace App\Services;

use App\Events\Clinical\TreatmentAdded;
use App\Models\Visit;
use App\Models\VisitTreatment;
use Illuminate\Support\Facades\DB;

/**
 * ClinicalTreatmentService - Business logic for clinical treatments.
 *
 * FASE 20.3: Canonical flow for treatment lifecycle management.
 * Treatments are independent entities that can be added, updated, and removed
 * from visits after the visit has been created.
 *
 * Emits:
 * - clinical.treatment.added
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
}
