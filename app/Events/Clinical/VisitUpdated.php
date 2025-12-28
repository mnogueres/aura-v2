<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * VisitUpdated - Emitted when a visit is updated (FASE 20.6).
 *
 * Payload contains complete POST-update state.
 */
class VisitUpdated extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $visit_id,
        int $patient_id,
        string $occurred_at,
        ?string $visit_type = null,
        ?string $summary = null,
        ?string $professional_id = null, // UUID string, not int
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
                'visit_id' => $visit_id,
                'patient_id' => $patient_id,
                'occurred_at' => $occurred_at,
                'visit_type' => $visit_type,
                'summary' => $summary,
                'professional_id' => $professional_id,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'clinical.visit.updated';
    }
}
