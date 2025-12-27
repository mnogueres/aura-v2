<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * Event: clinical.treatment.removed
 *
 * Emitted when: A treatment is removed/deleted (CANONICAL flow - FASE 20.4)
 *
 * Payload:
 * {
 *   "clinic_id": 1,
 *   "treatment_id": "uuid",
 *   "visit_id": "uuid",
 *   "patient_id": 123
 * }
 *
 * Note:
 * - Write model (VisitTreatment): soft deleted
 * - Read model (ClinicalTreatment): hard deleted by projector
 */
class TreatmentRemoved extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $treatment_id,
        string $visit_id,
        int $patient_id,
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
                'treatment_id' => $treatment_id,
                'visit_id' => $visit_id,
                'patient_id' => $patient_id,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'clinical.treatment.removed';
    }
}
