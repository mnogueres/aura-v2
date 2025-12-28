<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * ProfessionalUpdated - Emitted when a professional's information is updated
 *
 * FASE 21.0: Professional catalog
 *
 * Payload includes updated professional state.
 * Typically used for name or role changes.
 */
class ProfessionalUpdated extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $professional_id,
        string $name,
        string $role,
        bool $active,
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
        return 'clinical.professional.updated';
    }
}
