<?php

namespace App\Repositories;

use App\Models\ClinicalVisit;
use Illuminate\Support\Collection;

class ClinicalVisitRepository
{
    /**
     * Get all visits for a specific patient.
     *
     * @param int $clinicId
     * @param int $patientId
     * @return Collection<ClinicalVisit>
     */
    public function getVisitsForPatient(int $clinicId, int $patientId): Collection
    {
        return ClinicalVisit::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('occurred_at', 'desc')
            ->get();
    }

    /**
     * Get paginated visits for a specific patient.
     *
     * @param int $clinicId
     * @param int $patientId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getVisitsForPatientPaginated(int $clinicId, int $patientId, int $perPage = 25, int $page = 1)
    {
        return ClinicalVisit::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('occurred_at', 'desc')
            ->paginate($perPage, ['*'], 'visits_page', $page);
    }

    /**
     * Get a specific visit by ID.
     *
     * @param string $visitId
     * @return ClinicalVisit|null
     */
    public function getVisitById(string $visitId): ?ClinicalVisit
    {
        return ClinicalVisit::find($visitId);
    }
}
