<?php

namespace App\Projections;

use App\Events\Clinical\VisitRecorded;
use App\Models\ClinicalVisit;

class ClinicalVisitProjector
{
    public function handleVisitRecorded(VisitRecorded $event): void
    {
        $this->projectVisit($event);
    }

    private function projectVisit(VisitRecorded $event): void
    {
        $visitId = $event->payload['visit_id'];

        // Idempotency: use visit_id as unique identifier
        ClinicalVisit::firstOrCreate(
            ['id' => $visitId],
            [
                'clinic_id' => $event->payload['clinic_id'],
                'patient_id' => $event->payload['patient_id'],
                'occurred_at' => $event->payload['occurred_at'],
                'professional_id' => $event->payload['professional_id'],
                'visit_type' => $event->payload['visit_type'],
                'summary' => $event->payload['summary'],
                'treatments_count' => 0,
                'projected_at' => now(),
                'source_event_id' => $event->request_id,
            ]
        );
    }
}
