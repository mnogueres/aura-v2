<?php

namespace App\Repositories;

use App\Models\ClinicalProfessional;
use Illuminate\Database\Eloquent\Collection;

/**
 * ClinicalProfessionalRepository - Repository for ClinicalProfessional read model
 *
 * FASE 21.0: Professional catalog UI
 */
class ClinicalProfessionalRepository
{
    /**
     * Get all active professionals for a clinic, ordered alphabetically.
     *
     * @param int $clinicId
     * @return Collection<ClinicalProfessional>
     */
    public function getActiveProfessionals(int $clinicId): Collection
    {
        return ClinicalProfessional::forClinic($clinicId)
            ->active()
            ->alphabetical()
            ->get();
    }

    /**
     * Get all professionals (active and inactive) for a clinic, ordered alphabetically.
     *
     * @param int $clinicId
     * @return Collection<ClinicalProfessional>
     */
    public function getAllProfessionals(int $clinicId): Collection
    {
        return ClinicalProfessional::forClinic($clinicId)
            ->alphabetical()
            ->get();
    }

    /**
     * Find a professional by ID.
     *
     * @param string $id
     * @return ClinicalProfessional|null
     */
    public function findById(string $id): ?ClinicalProfessional
    {
        return ClinicalProfessional::find($id);
    }
}
