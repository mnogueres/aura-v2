<?php

namespace App\Repositories;

use App\Models\ClinicalTreatment;
use Illuminate\Support\Collection;

class ClinicalTreatmentRepository
{
    /**
     * Get all treatments for a specific visit.
     *
     * @param string $visitId
     * @return Collection<ClinicalTreatment>
     */
    public function getTreatmentsForVisit(string $visitId): Collection
    {
        return ClinicalTreatment::where('visit_id', $visitId)
            ->orderBy('projected_at')
            ->get();
    }

    /**
     * Get all treatments for a specific patient.
     *
     * @param int $clinicId
     * @param int $patientId
     * @return Collection<ClinicalTreatment>
     */
    public function getTreatmentsForPatient(int $clinicId, int $patientId): Collection
    {
        return ClinicalTreatment::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('projected_at', 'desc')
            ->get();
    }
}
