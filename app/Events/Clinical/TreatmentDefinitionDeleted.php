<?php

namespace App\Events\Clinical;

use App\Events\DomainEvent;

/**
 * FASE 20.7: TreatmentDefinitionDeleted event
 *
 * Emitted when a treatment definition is permanently deleted from the catalog.
 * CRITICAL: Can only be deleted if NEVER used in any visit.
 */
class TreatmentDefinitionDeleted extends DomainEvent
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
        return 'clinical.treatment_definition.deleted';
    }
}
