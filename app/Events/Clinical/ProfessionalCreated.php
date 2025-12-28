<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * ProfessionalCreated - Emitted when a new professional is added to the clinic
 *
 * FASE 21.0: Professional catalog
 *
 * Payload includes complete professional state at creation.
 * Professional is independent of system users.
 */
class ProfessionalCreated extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $professional_id,
        string $name,
        string $role,
        bool $active = true,
        ?int $professional_user_id = null,
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
                'professional_id' => $professional_id,
                'name' => $name,
                'role' => $role,
                'active' => $active,
                'professional_user_id' => $professional_user_id,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'clinical.professional.created';
    }
}
