<?php

namespace App\Repositories;

use App\Models\PatientTimeline;
use Illuminate\Support\Collection;

class PatientTimelineRepository
{
    /**
     * Get timeline for a specific patient.
     *
     * Returns events in chronological order (oldest first).
     *
     * @param int $clinicId
     * @param int $patientId
     * @return Collection<PatientTimeline>
     */
    public function getTimelineForPatient(int $clinicId, int $patientId): Collection
    {
        return PatientTimeline::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('occurred_at')
            ->get();
    }
}
