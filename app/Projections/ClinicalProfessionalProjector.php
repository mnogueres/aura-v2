<?php

namespace App\Projections;

use App\Events\Clinical\ProfessionalCreated;
use App\Events\Clinical\ProfessionalUpdated;
use App\Events\Clinical\ProfessionalDeactivated;
use App\Models\ClinicalProfessional;

/**
 * ClinicalProfessionalProjector - Projector for professional catalog (FASE 21.0)
 *
 * Projects Professional events into read model optimized for UI queries.
 * No hard deletes - deactivation is the canonical way to remove a professional.
 */
class ClinicalProfessionalProjector
{
    /**
     * Handle ProfessionalCreated (initial creation).
     */
    public function handleProfessionalCreated(ProfessionalCreated $event): void
    {
        $professionalId = $event->payload['professional_id'];

        // Idempotency: use updateOrCreate to handle duplicate events
        ClinicalProfessional::updateOrCreate(
            ['id' => $professionalId],
            [
                'clinic_id' => $event->payload['clinic_id'],
                'name' => $event->payload['name'],
                'role' => $event->payload['role'],
                'active' => $event->payload['active'],
                'user_id' => $event->payload['professional_user_id'] ?? null,
                'projected_at' => now(),
                'source_event_id' => $event->request_id,
            ]
        );
    }

    /**
     * Handle ProfessionalUpdated.
     *
     * Updates the projection with POST-update complete state.
     */
    public function handleProfessionalUpdated(ProfessionalUpdated $event): void
    {
        $professionalId = $event->payload['professional_id'];

        ClinicalProfessional::where('id', $professionalId)
            ->update([
                'name' => $event->payload['name'],
                'role' => $event->payload['role'],
                'active' => $event->payload['active'],
                'user_id' => $event->payload['professional_user_id'] ?? null,
                'projected_at' => now(),
            ]);
    }

    /**
     * Handle ProfessionalDeactivated.
     *
     * Marks professional as inactive but does NOT delete from read model.
     * Historical data preserved for reporting and audit.
     */
    public function handleProfessionalDeactivated(ProfessionalDeactivated $event): void
    {
        $professionalId = $event->payload['professional_id'];

        ClinicalProfessional::where('id', $professionalId)
            ->update([
                'active' => false,
                'projected_at' => now(),
            ]);
    }
}
