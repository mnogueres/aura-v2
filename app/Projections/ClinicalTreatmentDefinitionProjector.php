<?php

namespace App\Projections;

use App\Events\Clinical\TreatmentDefinitionCreated;
use App\Events\Clinical\TreatmentDefinitionUpdated;
use App\Events\Clinical\TreatmentDefinitionDeactivated;
use App\Events\Clinical\TreatmentDefinitionDeleted;
use App\Models\ClinicalTreatmentDefinition;

/**
 * ClinicalTreatmentDefinitionProjector - Projector for treatment catalog (FASE 20.5)
 *
 * Projects TreatmentDefinition events into read model optimized for UI queries.
 * Historical data preserved - deactivation only marks inactive, no hard deletes.
 */
class ClinicalTreatmentDefinitionProjector
{
    /**
     * Handle TreatmentDefinitionCreated (initial creation).
     */
    public function handleTreatmentDefinitionCreated(TreatmentDefinitionCreated $event): void
    {
        $this->projectTreatmentDefinition($event);
    }

    /**
     * Handle TreatmentDefinitionUpdated.
     *
     * Updates the projection with POST-update complete state.
     */
    public function handleTreatmentDefinitionUpdated(TreatmentDefinitionUpdated $event): void
    {
        $treatmentDefinitionId = $event->payload['treatment_definition_id'];

        ClinicalTreatmentDefinition::where('id', $treatmentDefinitionId)
            ->update([
                'name' => $event->payload['name'],
                'default_price' => $event->payload['default_price'],
                'active' => $event->payload['active'],
                'projected_at' => now(),
            ]);
    }

    /**
     * Handle TreatmentDefinitionDeactivated.
     *
     * Marks definition as inactive but does NOT delete from read model.
     * Historical data preserved for reporting and audit.
     */
    public function handleTreatmentDefinitionDeactivated(TreatmentDefinitionDeactivated $event): void
    {
        $treatmentDefinitionId = $event->payload['treatment_definition_id'];

        ClinicalTreatmentDefinition::where('id', $treatmentDefinitionId)
            ->update([
                'active' => false,
                'projected_at' => now(),
            ]);
    }

    /**
     * Handle TreatmentDefinitionDeleted (FASE 20.7).
     *
     * Permanently deletes the projection from read model.
     * This is only possible if the definition was NEVER used in visits.
     */
    public function handleTreatmentDefinitionDeleted(TreatmentDefinitionDeleted $event): void
    {
        $treatmentDefinitionId = $event->payload['treatment_definition_id'];

        // Hard delete from read model
        ClinicalTreatmentDefinition::where('id', $treatmentDefinitionId)->delete();
    }

    /**
     * Project a treatment definition from TreatmentDefinitionCreated event.
     *
     * @param TreatmentDefinitionCreated $event
     * @return void
     */
    private function projectTreatmentDefinition(TreatmentDefinitionCreated $event): void
    {
        $treatmentDefinitionId = $event->payload['treatment_definition_id'];

        // Idempotency: use treatment_definition_id as unique identifier
        ClinicalTreatmentDefinition::firstOrCreate(
            ['id' => $treatmentDefinitionId],
            [
                'clinic_id' => $event->payload['clinic_id'],
                'name' => $event->payload['name'],
                'default_price' => $event->payload['default_price'],
                'active' => $event->payload['active'],
                'projected_at' => now(),
                'source_event_id' => $event->request_id,
            ]
        );
    }
}
