<?php

namespace App\Events\CRM;

use App\Events\DomainEvent;

/**
 * Event: crm.patient.created
 *
 * Emitted when: POST /patients → 201
 *
 * Payload:
 * {
 *   "patient_id": 123
 * }
 */
class PatientCreated extends DomainEvent
{
    public function __construct(
        int $patient_id,
        ?string $request_id = null,
        ?int $user_id = null,
        ?int $clinic_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'patient_id' => $patient_id,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'crm.patient.created';
    }
}
