<?php

namespace App\Services;

use App\Events\Clinical\ProfessionalCreated;
use App\Events\Clinical\ProfessionalUpdated;
use App\Events\Clinical\ProfessionalDeactivated;
use App\Models\Professional;
use Illuminate\Support\Facades\DB;

/**
 * ClinicalProfessionalService - Domain service for professional catalog (FASE 21.0)
 *
 * Manages the catalog of clinical professionals per clinic.
 * Professionals are independent of system users.
 *
 * Responsibilities:
 * - Create, update, deactivate professionals
 * - Validate business rules (name required, role required)
 * - Emit domain events for projections
 *
 * NOT responsible for:
 * - Auth/permissions (professionals ≠ users)
 * - Facturación (out of scope for FASE 21.0)
 */
class ClinicalProfessionalService
{
    private const VALID_ROLES = ['dentist', 'hygienist', 'assistant', 'other'];

    public function __construct(
        private readonly EventService $eventService
    ) {
    }

    /**
     * Create a new professional in the clinic catalog.
     *
     * @param array $data
     * @return Professional
     * @throws \DomainException
     */
    public function createProfessional(array $data): Professional
    {
        $this->validateProfessionalData($data);

        return DB::transaction(function () use ($data) {
            $professional = Professional::create([
                'clinic_id' => $data['clinic_id'],
                'name' => $data['name'],
                'role' => $data['role'],
                'active' => $data['active'] ?? true,
                'user_id' => $data['user_id'] ?? null,
            ]);

            $this->eventService->emit(
                new ProfessionalCreated(
                    clinic_id: $professional->clinic_id,
                    professional_id: $professional->id,
                    name: $professional->name,
                    role: $professional->role,
                    active: $professional->active,
                    professional_user_id: $professional->user_id
                )
            );

            return $professional;
        });
    }

    /**
     * Update an existing professional.
     *
     * @param string $professionalId
     * @param array $updates
     * @return Professional
     * @throws \DomainException
     */
    public function updateProfessional(string $professionalId, array $updates): Professional
    {
        $this->validateProfessionalUpdates($updates);

        $professional = Professional::withTrashed()->find($professionalId);

        if (!$professional) {
            throw new \DomainException('Professional not found');
        }

        if ($professional->trashed()) {
            throw new \DomainException('Cannot update deleted professional');
        }

        return DB::transaction(function () use ($professional, $updates) {
            // Update only provided fields
            $professional->update(array_filter($updates, function ($value, $key) use ($updates) {
                return array_key_exists($key, $updates);
            }, ARRAY_FILTER_USE_BOTH));

            $professional->refresh();

            $this->eventService->emit(
                new ProfessionalUpdated(
                    clinic_id: $professional->clinic_id,
                    professional_id: $professional->id,
                    name: $professional->name,
                    role: $professional->role,
                    active: $professional->active,
                    professional_user_id: $professional->user_id
                )
            );

            return $professional;
        });
    }

    /**
     * Deactivate a professional.
     *
     * Deactivation is the canonical way to "remove" a professional.
     * No hard deletes allowed.
     *
     * @param string $professionalId
     * @return Professional
     * @throws \DomainException
     */
    public function deactivateProfessional(string $professionalId): Professional
    {
        $professional = Professional::find($professionalId);

        if (!$professional) {
            throw new \DomainException('Professional not found');
        }

        if (!$professional->active) {
            throw new \DomainException('Professional is already inactive');
        }

        return DB::transaction(function () use ($professional) {
            $professional->update(['active' => false]);

            $this->eventService->emit(
                new ProfessionalDeactivated(
                    clinic_id: $professional->clinic_id,
                    professional_id: $professional->id
                )
            );

            return $professional;
        });
    }

    /**
     * Validate professional data for creation.
     *
     * @param array $data
     * @throws \DomainException
     */
    private function validateProfessionalData(array $data): void
    {
        if (empty($data['name'])) {
            throw new \DomainException('Professional name is required');
        }

        if (empty($data['role'])) {
            throw new \DomainException('Professional role is required');
        }

        if (!in_array($data['role'], self::VALID_ROLES)) {
            throw new \DomainException('Invalid role. Must be one of: ' . implode(', ', self::VALID_ROLES));
        }

        if (empty($data['clinic_id'])) {
            throw new \DomainException('clinic_id is required');
        }
    }

    /**
     * Validate professional updates.
     *
     * @param array $updates
     * @throws \DomainException
     */
    private function validateProfessionalUpdates(array $updates): void
    {
        if (isset($updates['name']) && empty($updates['name'])) {
            throw new \DomainException('Professional name cannot be empty');
        }

        if (isset($updates['role'])) {
            if (empty($updates['role'])) {
                throw new \DomainException('Professional role cannot be empty');
            }

            if (!in_array($updates['role'], self::VALID_ROLES)) {
                throw new \DomainException('Invalid role. Must be one of: ' . implode(', ', self::VALID_ROLES));
            }
        }
    }
}
