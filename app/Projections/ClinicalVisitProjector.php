<?php

namespace App\Projections;

use App\Events\Clinical\VisitRecorded;
use App\Events\Clinical\VisitUpdated;
use App\Events\Clinical\VisitRemoved;
use App\Models\ClinicalVisit;

class ClinicalVisitProjector
{
    /**
     * Handle VisitRecorded (initial creation).
     */
    public function handleVisitRecorded(VisitRecorded $event): void
    {
        $this->projectVisit($event);
    }

    /**
     * Handle VisitUpdated (CANONICAL flow - FASE 20.6).
     *
     * Updates the projection with POST-update complete state.
     * Does NOT modify treatments_count (independent lifecycle).
     */
    public function handleVisitUpdated(VisitUpdated $event): void
    {
        $visitId = $event->payload['visit_id'];

        ClinicalVisit::where('id', $visitId)
            ->update([
                'occurred_at' => $event->payload['occurred_at'],
                'visit_type' => $event->payload['visit_type'],
                'summary' => $event->payload['summary'],
                'professional_id' => $event->payload['professional_id'],
                'projected_at' => now(),
            ]);
    }

    /**
     * Handle VisitRemoved (CANONICAL flow - FASE 20.6).
     *
     * Hard deletes from read model (ClinicalVisit).
     * Write model (Visit) is soft-deleted by service.
     * No treatments should exist at this point (validated by service).
     */
    public function handleVisitRemoved(VisitRemoved $event): void
    {
        $visitId = $event->payload['visit_id'];

        // Hard delete from read model (no deleted_at in ClinicalVisit)
        ClinicalVisit::where('id', $visitId)->delete();
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
