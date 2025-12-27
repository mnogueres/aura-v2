<?php

namespace App\Events\Admin;

use App\Events\DomainEvent;

/**
 * FASE 20.X: ClinicCreated event
 *
 * Emitted when a new clinic is created in the system.
 * Used to trigger automatic catalog initialization.
 */
class ClinicCreated extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $clinic_name,
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
                'clinic_name' => $clinic_name,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'admin.clinic.created';
    }
}
