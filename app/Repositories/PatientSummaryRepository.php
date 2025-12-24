<?php

namespace App\Repositories;

use App\Models\PatientSummary;

class PatientSummaryRepository
{
    /**
     * Get summary for a specific patient.
     *
     * @param int $clinicId
     * @param int $patientId
     * @return PatientSummary|null
     */
    public function getByPatient(int $clinicId, int $patientId): ?PatientSummary
    {
        return PatientSummary::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->first();
    }
}
