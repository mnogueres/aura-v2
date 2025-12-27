<?php

namespace App\Projections;

use App\Events\Clinical\TreatmentRecorded;
use App\Models\ClinicalTreatment;
use App\Models\ClinicalVisit;

class ClinicalTreatmentProjector
{
    public function handleTreatmentRecorded(TreatmentRecorded $event): void
    {
        $this->projectTreatment($event);
        $this->incrementVisitTreatmentCount($event);
    }

    private function projectTreatment(TreatmentRecorded $event): void
    {
        $treatmentId = $event->payload['treatment_id'];

        // Idempotency: use treatment_id as unique identifier
        ClinicalTreatment::firstOrCreate(
            ['id' => $treatmentId],
            [
                'clinic_id' => $event->payload['clinic_id'],
                'patient_id' => $event->payload['patient_id'],
                'visit_id' => $event->payload['visit_id'],
                'type' => $event->payload['type'],
                'tooth' => $event->payload['tooth'],
                'amount' => $event->payload['amount'],
                'notes' => $event->payload['notes'],
                'projected_at' => now(),
                'source_event_id' => $event->request_id,
            ]
        );
    }

    private function incrementVisitTreatmentCount(TreatmentRecorded $event): void
    {
        $visitId = $event->payload['visit_id'];

        $visit = ClinicalVisit::find($visitId);

        if ($visit) {
            $visit->increment('treatments_count');
        }
    }
}
