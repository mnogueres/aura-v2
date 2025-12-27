<?php

namespace App\Services;

use App\Events\Clinical\TreatmentDefinitionCreated;
use App\Events\Clinical\TreatmentDefinitionUpdated;
use App\Events\Clinical\TreatmentDefinitionDeactivated;
use App\Models\TreatmentDefinition;
use Illuminate\Support\Facades\DB;

/**
 * ClinicalTreatmentCatalogService - Domain service for treatment catalog (FASE 20.5)
 *
 * Manages the stable catalog of treatment definitions per clinic.
 * Prices are reference only - actual treatment prices can differ per visit.
 *
 * Responsibilities:
 * - Create, update, deactivate treatment definitions
 * - Validate business rules (name required, price >= 0)
 * - Emit domain events for projections
 *
 * NOT responsible for:
 * - Facturación (out of scope for FASE 20.5)
 * - Creating actual visit treatments (handled by ClinicalTreatmentService)
 */
class ClinicalTreatmentCatalogService
{
    public function __construct(
        private readonly EventService $eventService
    ) {
    }

    /**
     * Create a new treatment definition in the catalog.
     *
     * @param array $data
     * @return TreatmentDefinition
     * @throws \DomainException
     */
    public function createTreatmentDefinition(array $data): TreatmentDefinition
    {
        $this->validateTreatmentDefinitionData($data);

        return DB::transaction(function () use ($data) {
            $definition = TreatmentDefinition::create([
                'clinic_id' => $data['clinic_id'],
                'name' => $data['name'],
                'default_price' => $data['default_price'] ?? null,
                'active' => $data['active'] ?? true,
            ]);

            $this->eventService->emit(
                new TreatmentDefinitionCreated(
                    clinic_id: $definition->clinic_id,
                    treatment_definition_id: $definition->id,
                    name: $definition->name,
                    default_price: $definition->default_price ? (float) $definition->default_price : null,
                    active: $definition->active
                )
            );

            return $definition;
        });
    }

    /**
     * Update an existing treatment definition.
     *
     * Changing default_price does NOT affect already created VisitTreatments.
     *
     * @param string $treatmentDefinitionId
     * @param array $updates
     * @return TreatmentDefinition
     * @throws \DomainException
     */
    public function updateTreatmentDefinition(string $treatmentDefinitionId, array $updates): TreatmentDefinition
    {
        $this->validateTreatmentDefinitionUpdates($updates);

        $definition = TreatmentDefinition::withTrashed()->find($treatmentDefinitionId);

        if (!$definition) {
            throw new \DomainException('Treatment definition not found');
        }

        if ($definition->trashed()) {
            throw new \DomainException('Cannot update deleted treatment definition');
        }

        return DB::transaction(function () use ($definition, $updates) {
            // Update only provided fields
            $definition->update(array_filter($updates, function ($value, $key) use ($updates) {
                return array_key_exists($key, $updates);
            }, ARRAY_FILTER_USE_BOTH));

            $definition->refresh();

            $this->eventService->emit(
                new TreatmentDefinitionUpdated(
                    clinic_id: $definition->clinic_id,
                    treatment_definition_id: $definition->id,
                    name: $definition->name,
                    default_price: $definition->default_price ? (float) $definition->default_price : null,
                    active: $definition->active
                )
            );

            return $definition;
        });
    }

    /**
     * Deactivate a treatment definition.
     *
     * Does NOT delete the definition - just marks it as inactive.
     * Inactive definitions won't appear in UI selection but remain in historical data.
     *
     * @param string $treatmentDefinitionId
     * @return void
     * @throws \DomainException
     */
    public function deactivateTreatmentDefinition(string $treatmentDefinitionId): void
    {
        $definition = TreatmentDefinition::withTrashed()->find($treatmentDefinitionId);

        if (!$definition) {
            throw new \DomainException('Treatment definition not found');
        }

        if ($definition->trashed()) {
            throw new \DomainException('Treatment definition already deleted');
        }

        if (!$definition->active) {
            throw new \DomainException('Treatment definition already inactive');
        }

        DB::transaction(function () use ($definition) {
            $clinic_id = $definition->clinic_id;
            $treatment_definition_id = $definition->id;

            $definition->update(['active' => false]);

            $this->eventService->emit(
                new TreatmentDefinitionDeactivated(
                    clinic_id: $clinic_id,
                    treatment_definition_id: $treatment_definition_id
                )
            );
        });
    }

    /**
     * Delete a treatment definition (FASE 20.7).
     *
     * CRITICAL: Can ONLY delete if the definition has NEVER been used in any visit.
     * If it has usage, throw exception.
     *
     * @param string $treatmentDefinitionId
     * @return void
     * @throws \DomainException
     */
    public function deleteTreatmentDefinition(string $treatmentDefinitionId): void
    {
        $definition = TreatmentDefinition::withTrashed()->find($treatmentDefinitionId);

        if (!$definition) {
            throw new \DomainException('Treatment definition not found');
        }

        if ($definition->trashed()) {
            throw new \DomainException('Treatment definition already deleted');
        }

        // CRITICAL: Check if definition has been used in any visit
        $usageCount = \App\Models\VisitTreatment::withTrashed()
            ->where('treatment_definition_id', $treatmentDefinitionId)
            ->count();

        if ($usageCount > 0) {
            throw new \DomainException('Cannot delete treatment definition: it has been used in ' . $usageCount . ' visit(s). You can only deactivate it.');
        }

        DB::transaction(function () use ($definition) {
            $clinic_id = $definition->clinic_id;
            $treatment_definition_id = $definition->id;

            // Soft delete (write model)
            $definition->delete();

            // Emit event for projector to hard delete from read model
            $this->eventService->emit(
                new \App\Events\Clinical\TreatmentDefinitionDeleted(
                    clinic_id: $clinic_id,
                    treatment_definition_id: $treatment_definition_id
                )
            );
        });
    }

    /**
     * Validate treatment definition data for creation.
     *
     * @param array $data
     * @return void
     * @throws \DomainException
     */
    private function validateTreatmentDefinitionData(array $data): void
    {
        if (empty($data['clinic_id'])) {
            throw new \DomainException('clinic_id is required');
        }

        if (empty($data['name'])) {
            throw new \DomainException('name is required');
        }

        if (isset($data['default_price']) && $data['default_price'] !== null) {
            if (!is_numeric($data['default_price'])) {
                throw new \DomainException('default_price must be numeric');
            }

            if ($data['default_price'] < 0) {
                throw new \DomainException('default_price must be >= 0');
            }
        }
    }

    /**
     * Validate treatment definition updates.
     *
     * @param array $updates
     * @return void
     * @throws \DomainException
     */
    private function validateTreatmentDefinitionUpdates(array $updates): void
    {
        if (isset($updates['name']) && empty($updates['name'])) {
            throw new \DomainException('name cannot be empty');
        }

        if (isset($updates['default_price']) && $updates['default_price'] !== null) {
            if (!is_numeric($updates['default_price'])) {
                throw new \DomainException('default_price must be numeric');
            }

            if ($updates['default_price'] < 0) {
                throw new \DomainException('default_price must be >= 0');
            }
        }
    }
}
