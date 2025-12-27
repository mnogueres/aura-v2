<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * VisitRemoved - Emitted when a visit is removed (FASE 20.6).
 *
 * Minimal payload with just IDs.
 */
class VisitRemoved extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $visit_id,
        int $patient_id,
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
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
        return 'clinical.visit.removed';
    }
}
