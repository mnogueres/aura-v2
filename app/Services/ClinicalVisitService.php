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
 * Future phase will introduce:
 * - AddTreatmentToVisit command (separate operation)
 * - Proper treatment lifecycle management
 *
 * Emits:
 * - clinical.visit.recorded
 * - clinical.treatment.recorded (if treatments provided)
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
}
