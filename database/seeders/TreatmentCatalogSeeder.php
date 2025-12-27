<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Services\ClinicalTreatmentCatalogService;
use App\Services\OutboxEventConsumer;
use Illuminate\Database\Seeder;

/**
 * FASE 20.X: Seed base treatment catalog for existing clinics
 *
 * Creates 20 standard dental treatments for each clinic that doesn't have them yet.
 * This seeder can be run manually: php artisan db:seed --class=TreatmentCatalogSeeder
 */
class TreatmentCatalogSeeder extends Seeder
{
    public function __construct(
        private readonly ClinicalTreatmentCatalogService $catalogService,
        private readonly OutboxEventConsumer $outboxConsumer
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding base treatment catalogs...');

        $clinics = Clinic::all();

        if ($clinics->isEmpty()) {
            $this->command->warn('No clinics found. Create a clinic first.');
            return;
        }

        foreach ($clinics as $clinic) {
            $this->seedCatalogForClinic($clinic);
        }

        $this->command->info('Base treatment catalogs seeded successfully!');
    }

    /**
     * Seed base catalog for a single clinic.
     */
    private function seedCatalogForClinic(Clinic $clinic): void
    {
        $this->command->info("Seeding catalog for clinic: {$clinic->name}");

        // Check if clinic already has treatments
        $existingCount = \App\Models\ClinicalTreatmentDefinition::forClinic($clinic->id)->count();

        if ($existingCount > 0) {
            $this->command->warn("  Clinic already has {$existingCount} treatments. Skipping.");
            return;
        }

        $baseTreatments = $this->getBaseTreatments();

        foreach ($baseTreatments as $treatmentName) {
            $this->catalogService->createTreatmentDefinition([
                'clinic_id' => $clinic->id,
                'name' => $treatmentName,
                'default_price' => null, // No default prices - clinic sets them
                'active' => true,
            ]);
        }

        // Process all events immediately
        $this->outboxConsumer->processPendingEvents();

        $this->command->info("  ✓ Created 20 base treatments for {$clinic->name}");
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
