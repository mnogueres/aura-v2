<?php

namespace App\Projections;

use App\Events\Clinical\TreatmentRecorded;
use App\Events\Clinical\TreatmentAdded;
use App\Events\Clinical\TreatmentUpdated;
use App\Events\Clinical\TreatmentRemoved;
use App\Models\ClinicalTreatment;
use App\Models\ClinicalVisit;

class ClinicalTreatmentProjector
{
    /**
     * Handle TreatmentRecorded (legacy inline creation from createVisit).
     */
    public function handleTreatmentRecorded(TreatmentRecorded $event): void
    {
        $this->projectTreatment($event);
        $this->incrementVisitTreatmentCount($event);
    }

    /**
     * Handle TreatmentAdded (CANONICAL flow - FASE 20.3).
     */
    public function handleTreatmentAdded(TreatmentAdded $event): void
    {
        $this->projectTreatment($event);
        $this->incrementVisitTreatmentCount($event);
    }

    /**
     * Handle TreatmentUpdated (CANONICAL flow - FASE 20.4).
     *
     * Updates the projection with POST-update complete state.
     * Does NOT modify treatments_count (same treatment).
     */
    public function handleTreatmentUpdated(TreatmentUpdated $event): void
    {
        $treatmentId = $event->payload['treatment_id'];

        ClinicalTreatment::where('id', $treatmentId)
            ->update([
                'type' => $event->payload['type'],
                'tooth' => $event->payload['tooth'],
                'amount' => $event->payload['amount'],
                'notes' => $event->payload['notes'],
                'projected_at' => now(),
            ]);
    }

    /**
     * Handle TreatmentRemoved (CANONICAL flow - FASE 20.4).
     *
     * Hard deletes from read model and decrements treatments_count.
     * Write model (VisitTreatment) is soft-deleted by service.
     */
    public function handleTreatmentRemoved(TreatmentRemoved $event): void
    {
        $treatmentId = $event->payload['treatment_id'];
        $visitId = $event->payload['visit_id'];

        // Hard delete from read model (no deleted_at in ClinicalTreatment)
        ClinicalTreatment::where('id', $treatmentId)->delete();

        // Decrement treatments_count
        $visit = ClinicalVisit::find($visitId);

        if ($visit && $visit->treatments_count > 0) {
            $visit->decrement('treatments_count');
        }
    }

    private function projectTreatment(TreatmentRecorded|TreatmentAdded $event): void
    {
        $treatmentId = $event->payload['treatment_id'];

        // Idempotency: use treatment_id as unique identifier
        ClinicalTreatment::firstOrCreate(
            ['id' => $treatmentId],
            [
                'clinic_id' => $event->payload['clinic_id'],
                'patient_id' => $event->payload['patient_id'],
                'visit_id' => $event->payload['visit_id'],
                'treatment_definition_id' => $event->payload['treatment_definition_id'] ?? null,  // FASE 20.5+
                'type' => $event->payload['type'],
                'tooth' => $event->payload['tooth'],
                'amount' => $event->payload['amount'],
                'notes' => $event->payload['notes'],
                'projected_at' => now(),
                'created_at' => now(),
                'source_event_id' => $event->request_id,
            ]
        );
    }

    private function incrementVisitTreatmentCount(TreatmentRecorded|TreatmentAdded $event): void
    {
        $visitId = $event->payload['visit_id'];

        $visit = ClinicalVisit::find($visitId);

        if ($visit) {
            $visit->increment('treatments_count');
        }
    }
}
