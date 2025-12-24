<?php

namespace App\Repositories;

use App\Models\BillingTimeline;
use Illuminate\Support\Collection;

class BillingTimelineRepository
{
    /**
     * Get billing timeline for a specific patient.
     *
     * Returns events in chronological order (oldest first).
     *
     * @param int $clinicId
     * @param int $patientId
     * @return Collection<BillingTimeline>
     */
    public function getForPatient(int $clinicId, int $patientId): Collection
    {
        return BillingTimeline::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->orderBy('occurred_at')
            ->get();
    }

    /**
     * Get billing timeline for a clinic.
     *
     * Returns events in chronological order (oldest first).
     *
     * @param int $clinicId
     * @return Collection<BillingTimeline>
     */
    public function getForClinic(int $clinicId): Collection
    {
        return BillingTimeline::where('clinic_id', $clinicId)
            ->orderBy('occurred_at')
            ->get();
    }
}
