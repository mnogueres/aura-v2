<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * Event: clinical.treatment.recorded
 *
 * Emitted when: A treatment is recorded as part of a visit
 *
 * Payload:
 * {
 *   "treatment_id": "uuid",
 *   "visit_id": "uuid",
 *   "patient_id": 123,
 *   "type": "Empaste",
 *   "tooth": "16",
 *   "amount": "65.00",
 *   "notes": "Composite fotopolimerizable clase II"
 * }
 */
class TreatmentRecorded extends DomainEvent
{
    public function __construct(
        string $treatment_id,
        string $visit_id,
        int $patient_id,
        string $type,
        ?string $tooth = null,
        ?string $amount = null,
        ?string $notes = null,
        ?string $request_id = null,
        ?int $user_id = null,
        ?int $clinic_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'treatment_id' => $treatment_id,
                'visit_id' => $visit_id,
                'patient_id' => $patient_id,
                'type' => $type,
                'tooth' => $tooth,
                'amount' => $amount,
                'notes' => $notes,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'clinical.treatment.recorded';
    }
}
