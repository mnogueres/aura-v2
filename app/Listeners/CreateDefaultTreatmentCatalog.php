<?php

namespace App\Listeners;

use App\Events\Admin\ClinicCreated;
use App\Services\ClinicalTreatmentCatalogService;
use App\Services\OutboxEventConsumer;

/**
 * FASE 20.X: Create default treatment catalog when clinic is created
 *
 * Every new clinic gets a base catalog of 20 standard dental treatments.
 * These are NOT global - each clinic has its own copy that can be modified.
 */
class CreateDefaultTreatmentCatalog
{
    public function __construct(
        private readonly ClinicalTreatmentCatalogService $catalogService,
        private readonly OutboxEventConsumer $outboxConsumer
    ) {
    }

    /**
     * Handle the ClinicCreated event.
     */
    public function handle(ClinicCreated $event): void
    {
        $clinicId = $event->payload['clinic_id'];

        // Base catalog of 20 standard dental treatments
        $baseTreatments = $this->getBaseTreatments();

        foreach ($baseTreatments as $treatmentName) {
            $this->catalogService->createTreatmentDefinition([
                'clinic_id' => $clinicId,
                'name' => $treatmentName,
                'default_price' => null, // No default prices - clinic sets them
                'active' => true,
            ]);
        }

        // Process all events immediately
        $this->outboxConsumer->processPendingEvents();
    }

    /**
     * Get the exact list of 20 base treatments.
     *
     * @return array<string>
     */
    private function getBaseTreatments(): array
    {
        return [
            'Primera visita',
            'Revisión',
            'Visita',
            'Higiene / Limpieza dental',
            'Obturación (empaste)',
            'Endodoncia',
            'Reendodoncia',
            'Exodoncia',
            'Exodoncia quirúrgica',
            'Implante',
            'Mantenimiento de implantes',
            'Corona',
            'Prótesis completa',
            'Prótesis parcial',
            'Reconstrucción',
            'Reconstrucción con poste',
            'Férula de descarga',
            'Blanqueamiento dental',
            'Radiografía',
            'Ortopantomografía',
        ];
    }
}
