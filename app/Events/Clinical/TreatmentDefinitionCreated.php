<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * TreatmentDefinitionCreated - Emitted when a new treatment definition is added to catalog
 *
 * FASE 20.5: Treatment catalog
 *
 * Payload includes complete treatment definition state.
 * default_price is reference only, not binding for actual treatments.
 */
class TreatmentDefinitionCreated extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $treatment_definition_id,
        string $name,
        ?float $default_price = null,
        bool $active = true,
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
                'treatment_definition_id' => $treatment_definition_id,
                'name' => $name,
                'default_price' => $default_price,
                'active' => $active,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'clinical.treatment_definition.created';
    }
}
