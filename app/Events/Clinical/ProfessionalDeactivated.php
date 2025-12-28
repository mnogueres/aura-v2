<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * ProfessionalDeactivated - Emitted when a professional is deactivated
 *
 * FASE 21.0: Professional catalog
 *
 * Deactivation is the canonical way to "remove" a professional.
 * No hard deletes in this domain.
 */
class ProfessionalDeactivated extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $professional_id,
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
                'professional_id' => $professional_id,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'clinical.professional.deactivated';
    }
}
