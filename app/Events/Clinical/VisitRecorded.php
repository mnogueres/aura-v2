<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * Event: clinical.visit.recorded
 *
 * Emitted when: A clinical visit is recorded in the system
 *
 * Payload:
 * {
 *   "visit_id": "uuid",
 *   "patient_id": 123,
 *   "occurred_at": "2025-12-25T10:30:00Z",
 *   "professional_name": "Dr. García",
 *   "visit_type": "Primera visita",
 *   "summary": "Paciente refiere dolor en molar inferior derecho"
 * }
 */
class VisitRecorded extends DomainEvent
{
    public function __construct(
        string $visit_id,
        int $patient_id,
        string $occurred_at,
        string $professional_name,
        ?string $visit_type = null,
        ?string $summary = null,
        ?string $request_id = null,
        ?int $user_id = null,
        ?int $clinic_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'visit_id' => $visit_id,
                'patient_id' => $patient_id,
                'occurred_at' => $occurred_at,
                'professional_name' => $professional_name,
                'visit_type' => $visit_type,
                'summary' => $summary,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'clinical.visit.recorded';
    }
}
