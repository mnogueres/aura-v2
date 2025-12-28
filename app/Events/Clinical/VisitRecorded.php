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
 *   "clinic_id": 1,
 *   "visit_id": "uuid",
 *   "patient_id": 123,
 *   "professional_id": 42,
 *   "occurred_at": "2025-12-27T10:30:00Z",
 *   "visit_type": "Primera visita",
 *   "summary": "Paciente refiere dolor en molar inferior derecho"
 * }
 */
class VisitRecorded extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $visit_id,
        int $patient_id,
        ?string $professional_id, // UUID string, not int
        string $occurred_at,
        ?string $visit_type = null,
        ?string $summary = null,
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
                'visit_id' => $visit_id,
                'patient_id' => $patient_id,
                'professional_id' => $professional_id,
                'occurred_at' => $occurred_at,
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
