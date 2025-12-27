<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\VisitTreatment;
use App\Models\TreatmentDefinition;
use App\Models\ClinicalTreatmentDefinition;
use App\Services\ClinicalTreatmentCatalogService;
use App\Services\ClinicalTreatmentService;
use App\Services\OutboxEventConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 20.5: Feature tests for Treatment Catalog integration
 *
 * Critical test: Price changes in catalog do NOT affect existing treatments
 */
class ClinicalTreatmentCatalogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private Patient $patient;
    private Visit $visit;
    private ClinicalTreatmentCatalogService $catalogService;
    private ClinicalTreatmentService $treatmentService;
    private OutboxEventConsumer $outboxConsumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogService = app(ClinicalTreatmentCatalogService::class);
        $this->treatmentService = app(ClinicalTreatmentService::class);
        $this->outboxConsumer = app(OutboxEventConsumer::class);

        $this->clinic = Clinic::create([
            'name' => 'Test Clinic',
            'dni' => '12345678A',
            'address' => 'Test Address',
            'phone' => '123456789',
        ]);

        $this->patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dni' => '87654321B',
            'email' => 'john@example.com',
            'phone' => '987654321',
        ]);

        $this->visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
        ]);
    }

    /**
     * CRITICAL TEST (FASE 20.5):
     * Changing the default_price in catalog does NOT affect existing treatments.
     * Treatments store a snapshot of the price at creation time.
     */
    public function test_catalog_price_changes_do_not_affect_existing_treatments(): void
    {
        // 1. Create treatment definition with original price
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Empaste Composite',
            'default_price' => 65.00,
        ]);

        // Process events to create read model projection
        $this->outboxConsumer->processPendingEvents();

        // 2. Create treatment using catalog definition (with original price)
        $treatment = $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'treatment_definition_id' => $definition->id,
            'type' => 'Empaste Composite', // This gets auto-filled from catalog
            'tooth' => '16',
            'amount' => 65.00, // This gets auto-filled from catalog
        ]);

        // Verify treatment has original price
        $this->assertEquals(65.00, $treatment->amount);

        // 3. Update catalog definition with NEW price
        $this->catalogService->updateTreatmentDefinition($definition->id, [
            'default_price' => 85.00, // Price increased!
        ]);

        // Process events
        $this->outboxConsumer->processPendingEvents();

        // 4. CRITICAL ASSERTION: Existing treatment still has ORIGINAL price
        $treatment->refresh();
        $this->assertEquals(65.00, $treatment->amount); // NOT 85.00!

        // 5. Verify catalog definition has new price
        $definition->refresh();
        $this->assertEquals(85.00, $definition->default_price);

        // 6. New treatments created after price change get NEW price
        $newVisit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Segunda visita',
        ]);

        $newTreatment = $this->treatmentService->addTreatmentToVisit($newVisit->id, [
            'treatment_definition_id' => $definition->id,
            'type' => 'Empaste Composite',
            'amount' => 85.00, // New price
        ]);

        $this->assertEquals(85.00, $newTreatment->amount);
    }

    /**
     * Test: Treatment created from catalog stores the definition name as 'type' (snapshot)
     */
    public function test_catalog_treatment_snapshots_definition_name(): void
    {
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Limpieza Dental Profunda',
            'default_price' => 40.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        $treatment = $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'treatment_definition_id' => $definition->id,
            'type' => 'Limpieza Dental Profunda', // Snapshot from catalog
            'amount' => 40.00,
        ]);

        // Verify treatment has snapshotted name
        $this->assertEquals('Limpieza Dental Profunda', $treatment->type);
        $this->assertEquals($definition->id, $treatment->treatment_definition_id);

        // Update catalog name
        $this->catalogService->updateTreatmentDefinition($definition->id, [
            'name' => 'Limpieza Premium',
        ]);

        // Existing treatment still has original name (snapshot)
        $treatment->refresh();
        $this->assertEquals('Limpieza Dental Profunda', $treatment->type); // NOT 'Limpieza Premium'
    }

    /**
     * Test: Manual entry is NOT allowed (FASE 20.X)
     *
     * FASE 20.X: Catalog is REQUIRED - manual entry should fail
     */
    public function test_manual_treatment_entry_without_catalog(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('treatment_definition_id is required');

        // This should FAIL - manual entry not allowed
        $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'type' => 'Tratamiento Custom',
            'tooth' => '21',
            'amount' => 120.00,
            'notes' => 'Manual entry test',
        ]);
    }

    /**
     * Test: User can override catalog default_price when creating treatment
     */
    public function test_user_can_override_catalog_default_price(): void
    {
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Endodoncia',
            'default_price' => 150.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // User selects catalog item but changes the price
        $treatment = $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'treatment_definition_id' => $definition->id,
            'type' => 'Endodoncia',
            'amount' => 180.00, // User overrode the default price!
        ]);

        // Treatment has user-provided price, not catalog default
        $this->assertEquals(180.00, $treatment->amount);
        $this->assertEquals($definition->id, $treatment->treatment_definition_id);
    }

    /**
     * Test: Catalog selection is REQUIRED (FASE 20.X)
     *
     * FASE 20.X: treatment_definition_id is NO LONGER nullable
     */
    public function test_treatment_definition_id_is_optional(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('treatment_definition_id is required');

        // This should FAIL - catalog is required
        $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'type' => 'Consulta',
        ]);
    }

    /**
     * Test: Enrichment from catalog populates type and amount
     */
    public function test_catalog_enrichment_populates_type_and_amount(): void
    {
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Extracción Simple',
            'default_price' => 80.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Only provide treatment_definition_id
        $treatment = $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'treatment_definition_id' => $definition->id,
        ]);

        // Type and amount auto-populated from catalog
        $this->assertEquals('Extracción Simple', $treatment->type);
        $this->assertEquals(80.00, $treatment->amount);
    }

    /**
     * Test: Using inactive catalog definition throws error
     */
    public function test_cannot_use_inactive_catalog_definition(): void
    {
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Old Treatment',
            'default_price' => 50.00,
        ]);

        // Deactivate definition
        $this->catalogService->deactivateTreatmentDefinition($definition->id);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment definition not found or inactive');

        $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'treatment_definition_id' => $definition->id,
        ]);
    }

    /**
     * Test: Using catalog definition from different clinic throws error
     */
    public function test_cannot_use_catalog_definition_from_different_clinic(): void
    {
        $otherClinic = Clinic::create([
            'name' => 'Other Clinic',
            'dni' => '98765432Z',
            'address' => 'Other Address',
            'phone' => '987654321',
        ]);

        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $otherClinic->id,
            'name' => 'Other Clinic Treatment',
            'default_price' => 100.00,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment definition not found or inactive');

        // Try to use other clinic's definition
        $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'treatment_definition_id' => $definition->id,
        ]);
    }

    /**
     * Test: Catalog definition without default_price enriches only type
     */
    public function test_catalog_without_price_enriches_only_type(): void
    {
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Consulta Gratuita',
            // No default_price
        ]);

        $this->outboxConsumer->processPendingEvents();

        $treatment = $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'treatment_definition_id' => $definition->id,
        ]);

        // Type populated from catalog
        $this->assertEquals('Consulta Gratuita', $treatment->type);
        // Amount not populated (no default_price in catalog)
        $this->assertNull($treatment->amount);
    }

    /**
     * Test: Manual amount overrides catalog default_price when both provided
     */
    public function test_manual_amount_overrides_catalog_default_price(): void
    {
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Corona Dental',
            'default_price' => 300.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Provide both treatment_definition_id and manual amount
        $treatment = $this->treatmentService->addTreatmentToVisit($this->visit->id, [
            'treatment_definition_id' => $definition->id,
            'amount' => 350.00, // Manual override
        ]);

        // Manual amount takes precedence
        $this->assertEquals(350.00, $treatment->amount);
        $this->assertEquals('Corona Dental', $treatment->type);
    }
}
