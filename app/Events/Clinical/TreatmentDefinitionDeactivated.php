<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * TreatmentDefinitionDeactivated - Emitted when a treatment definition is deactivated
 *
 * FASE 20.5: Treatment catalog
 *
 * Deactivation does not delete the definition - it just marks it as inactive.
 * Inactive definitions won't appear in UI selection but remain in historical data.
 */
class TreatmentDefinitionDeactivated extends DomainEvent
{
    public function __construct(
        int $clinic_id,
        string $treatment_definition_id,
        ?string $request_id = null,
        ?int $user_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'clinic_id' => $clinic_id,
                'treatment_definition_id' => $treatment_definition_id,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'clinical.treatment_definition.deactivated';
    }
}
