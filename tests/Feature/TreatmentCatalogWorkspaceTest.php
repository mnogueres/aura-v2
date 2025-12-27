<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\TreatmentDefinition;
use App\Models\ClinicalTreatmentDefinition;
use App\Services\ClinicalTreatmentCatalogService;
use App\Services\ClinicalTreatmentService;
use App\Services\OutboxEventConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 20.7: Feature tests for Treatment Catalog Workspace UI
 *
 * Tests the workspace UI for managing treatment catalog, including
 * integration with visit treatment selector.
 */
class TreatmentCatalogWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private ClinicalTreatmentCatalogService $catalogService;
    private OutboxEventConsumer $outboxConsumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogService = app(ClinicalTreatmentCatalogService::class);
        $this->outboxConsumer = app(OutboxEventConsumer::class);

        $this->clinic = Clinic::create([
            'name' => 'Test Clinic',
            'dni' => '12345678A',
            'address' => 'Test Address',
            'phone' => '123456789',
        ]);

        // Mock clinic context
        app()->instance('currentClinicId', $this->clinic->id);
    }

    /**
     * Test: Treatment catalog index is accessible and shows treatments
     */
    public function test_treatment_catalog_index_is_visible(): void
    {
        // Create some treatments
        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Empaste Composite',
            'default_price' => 65.00,
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Limpieza Dental',
            'default_price' => 40.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Visit catalog page
        $response = $this->get(route('workspace.treatments.index'));

        $response->assertStatus(200);
        $response->assertSee('Tratamientos'); // Page title
        $response->assertSee('Empaste Composite');
        $response->assertSee('Limpieza Dental');
        $response->assertSee('65.00');
        $response->assertSee('40.00');
    }

    /**
     * Test: Creating a treatment appears in the listing
     */
    public function test_creating_treatment_appears_in_listing(): void
    {
        // Create treatment via POST
        $response = $this->post(route('workspace.treatments.store'), [
            'name' => 'Endodoncia',
            'default_price' => 150.00,
        ]);

        $response->assertStatus(200);

        // Verify it appears in the listing
        $listingResponse = $this->get(route('workspace.treatments.index'));
        $listingResponse->assertSee('Endodoncia');
        $listingResponse->assertSee('150.00');
    }

    /**
     * Test: Editing treatment name and price is reflected in listing
     */
    public function test_editing_treatment_reflects_in_listing(): void
    {
        // Create treatment
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Original Name',
            'default_price' => 50.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Edit treatment
        $response = $this->patch(route('workspace.treatment-definitions.update', ['treatmentDefinition' => $definition->id]), [
            'name' => 'Updated Name',
            'default_price' => 75.00,
        ]);

        $response->assertStatus(200);

        // Verify changes in listing
        $listingResponse = $this->get(route('workspace.treatments.index'));
        $listingResponse->assertSee('Updated Name');
        $listingResponse->assertSee('75.00');
        $listingResponse->assertDontSee('Original Name');
        $listingResponse->assertDontSee('50.00');
    }

    /**
     * Test: Deactivating treatment hides it from visit selector
     */
    public function test_deactivating_treatment_hides_from_visit_selector(): void
    {
        // Create active treatment
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test Treatment',
            'default_price' => 100.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Verify it's in the active catalog (would appear in selectors)
        $activeDefinitions = ClinicalTreatmentDefinition::forClinic($this->clinic->id)
            ->active()
            ->get();

        $this->assertTrue($activeDefinitions->contains('name', 'Test Treatment'));

        // Deactivate treatment
        $this->patch(route('workspace.treatment-definitions.toggle-active', ['treatmentDefinition' => $definition->id]));
        $this->outboxConsumer->processPendingEvents();

        // Verify it's NO LONGER in the active catalog (won't appear in selectors)
        $activeDefinitionsAfter = ClinicalTreatmentDefinition::forClinic($this->clinic->id)
            ->active()
            ->get();

        $this->assertFalse($activeDefinitionsAfter->contains('name', 'Test Treatment'));

        // But it still exists in the full catalog (not hard deleted)
        $allDefinitions = ClinicalTreatmentDefinition::forClinic($this->clinic->id)->get();
        $this->assertTrue($allDefinitions->contains('name', 'Test Treatment'));
    }

    /**
     * CRITICAL TEST (FASE 20.7):
     * Changing catalog price does NOT affect existing treatments in visits
     */
    public function test_catalog_changes_do_not_affect_existing_visit_treatments(): void
    {
        // Setup patient and visit
        $patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dni' => '11111111A',
            'email' => 'jane@example.com',
            'phone' => '111111111',
        ]);

        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $patient->id,
            'occurred_at' => now(),
        ]);

        // Create treatment definition
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Corona Dental',
            'default_price' => 300.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Create visit treatment using catalog (FASE 20.5)
        $treatmentService = app(ClinicalTreatmentService::class);
        $visitTreatment = $treatmentService->addTreatmentToVisit($visit->id, [
            'treatment_definition_id' => $definition->id,
            'type' => 'Corona Dental',
            'amount' => 300.00,
        ]);

        // Verify initial state
        $this->assertEquals(300.00, $visitTreatment->amount);

        // Update catalog price
        $this->patch(route('workspace.treatment-definitions.update', ['treatmentDefinition' => $definition->id]), [
            'default_price' => 400.00, // Price increased!
        ]);

        $this->outboxConsumer->processPendingEvents();

        // CRITICAL ASSERTION: Existing visit treatment still has original price
        $visitTreatment->refresh();
        $this->assertEquals(300.00, $visitTreatment->amount); // NOT 400.00!

        // Verify catalog has new price
        $catalogDefinition = ClinicalTreatmentDefinition::find($definition->id);
        $this->assertEquals(400.00, $catalogDefinition->default_price);
    }

    /**
     * Test: Empty state is shown when no treatments exist
     */
    public function test_empty_state_shown_when_no_treatments(): void
    {
        $response = $this->get(route('workspace.treatments.index'));

        $response->assertStatus(200);
        $response->assertSee('Aún no has creado tratamientos');
    }

    /**
     * Test: Creating treatment without price works correctly
     */
    public function test_creating_treatment_without_price(): void
    {
        $response = $this->post(route('workspace.treatments.store'), [
            'name' => 'Consulta Gratuita',
        ]);

        $response->assertStatus(200);

        $listingResponse = $this->get(route('workspace.treatments.index'));
        $listingResponse->assertSee('Consulta Gratuita');
        $listingResponse->assertSee('Sin precio de referencia');
    }

    /**
     * Test: Toggle active works (activate and deactivate)
     */
    public function test_toggle_active_works_both_ways(): void
    {
        // Create active treatment
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test Treatment',
            'default_price' => 50.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Verify it's active
        $this->assertTrue($definition->active);

        // Deactivate
        $this->patch(route('workspace.treatment-definitions.toggle-active', ['treatmentDefinition' => $definition->id]));
        $this->outboxConsumer->processPendingEvents();

        $definition->refresh();
        $this->assertFalse($definition->active);

        // Reactivate
        $this->patch(route('workspace.treatment-definitions.toggle-active', ['treatmentDefinition' => $definition->id]));
        $this->outboxConsumer->processPendingEvents();

        $definition->refresh();
        $this->assertTrue($definition->active);
    }

    /**
     * Test: Treatments are listed alphabetically
     */
    public function test_treatments_listed_alphabetically(): void
    {
        // Create treatments in random order
        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Zirconio',
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Amalgama',
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Limpieza',
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Get treatments from DB
        $treatments = ClinicalTreatmentDefinition::forClinic($this->clinic->id)
            ->alphabetical()
            ->get();

        // Verify alphabetical order
        $this->assertEquals('Amalgama', $treatments[0]->name);
        $this->assertEquals('Limpieza', $treatments[1]->name);
        $this->assertEquals('Zirconio', $treatments[2]->name);
    }

    /**
     * Test: Search by name returns correct results
     */
    public function test_search_by_name_returns_correct_results(): void
    {
        // Create multiple treatments
        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Empaste Composite',
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Endodoncia Total',
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Limpieza Dental Profunda',
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Search for "Empaste"
        $response = $this->get(route('workspace.treatments.index', ['search' => 'Empaste']));

        $response->assertStatus(200);
        $response->assertSee('Empaste Composite');
        $response->assertDontSee('Endodoncia Total');
        $response->assertDontSee('Limpieza Dental Profunda');
    }

    /**
     * Test: Partial search works correctly
     */
    public function test_partial_search_works(): void
    {
        // Create treatments
        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Endodoncia Simple',
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Endodoncia Compleja',
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Blanqueamiento Dental',
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Partial search for "endo"
        $response = $this->get(route('workspace.treatments.index', ['search' => 'endo']));

        $response->assertStatus(200);
        $response->assertSee('Endodoncia Simple');
        $response->assertSee('Endodoncia Compleja');
        $response->assertDontSee('Blanqueamiento Dental');
    }

    /**
     * Test: Empty search returns full listing
     */
    public function test_empty_search_returns_full_listing(): void
    {
        // Create treatments
        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Tratamiento A',
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Tratamiento B',
        ]);

        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Tratamiento C',
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Empty search
        $response = $this->get(route('workspace.treatments.index', ['search' => '']));

        $response->assertStatus(200);
        $response->assertSee('Tratamiento A');
        $response->assertSee('Tratamiento B');
        $response->assertSee('Tratamiento C');
    }

    /**
     * Test: Search with no results shows empty state
     */
    public function test_search_with_no_results_shows_empty_state(): void
    {
        // Create treatments
        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Corona Zirconio',
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Search for non-existent treatment
        $response = $this->get(route('workspace.treatments.index', ['search' => 'Inexistente']));

        $response->assertStatus(200);
        $response->assertSee('Aún no has creado tratamientos');
        // Verify the actual treatment doesn't appear
        $this->assertStringNotContainsString('Corona Zirconio', strip_tags($response->content()));
    }

    /**
     * Test: Search is case-insensitive
     */
    public function test_search_is_case_insensitive(): void
    {
        // Create treatment
        $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Empaste Composite',
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Search with different cases
        $responseLower = $this->get(route('workspace.treatments.index', ['search' => 'empaste']));
        $responseUpper = $this->get(route('workspace.treatments.index', ['search' => 'EMPASTE']));
        $responseMixed = $this->get(route('workspace.treatments.index', ['search' => 'EmPaStE']));

        $responseLower->assertSee('Empaste Composite');
        $responseUpper->assertSee('Empaste Composite');
        $responseMixed->assertSee('Empaste Composite');
    }

    /**
     * FASE 20.7: Test deletion of treatment that has NEVER been used
     */
    public function test_can_delete_treatment_that_was_never_used(): void
    {
        // Create treatment
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Unused Treatment',
            'default_price' => 50.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Verify it exists in catalog
        $this->assertDatabaseHas('clinical_treatment_definitions', [
            'id' => $definition->id,
            'name' => 'Unused Treatment',
        ]);

        // Delete treatment (should succeed because never used)
        $response = $this->delete(route('workspace.treatment-definitions.destroy', ['treatmentDefinition' => $definition->id]));

        $response->assertStatus(200);

        // Verify it's HARD DELETED from read model (catalog)
        $this->assertDatabaseMissing('clinical_treatment_definitions', [
            'id' => $definition->id,
        ]);

        // Verify it's soft deleted from write model
        $this->assertSoftDeleted('treatment_definitions', [
            'id' => $definition->id,
        ]);
    }

    /**
     * FASE 20.7: Test CANNOT delete treatment that has been used in visits
     */
    public function test_cannot_delete_treatment_that_was_used_in_visits(): void
    {
        // Setup patient and visit
        $patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dni' => '22222222B',
            'email' => 'john@example.com',
            'phone' => '222222222',
        ]);

        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $patient->id,
            'occurred_at' => now(),
        ]);

        // Create treatment definition
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Used Treatment',
            'default_price' => 100.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Use treatment in visit
        $treatmentService = app(ClinicalTreatmentService::class);
        $treatmentService->addTreatmentToVisit($visit->id, [
            'treatment_definition_id' => $definition->id,
            'type' => 'Used Treatment',
            'amount' => 100.00,
        ]);

        // Attempt to delete treatment (should FAIL)
        $response = $this->delete(route('workspace.treatment-definitions.destroy', ['treatmentDefinition' => $definition->id]));

        $response->assertStatus(422); // Validation error
        $response->assertJson(['error' => 'Cannot delete treatment definition: it has been used in 1 visit(s). You can only deactivate it.']);

        // Verify it STILL EXISTS in catalog (not deleted)
        $this->assertDatabaseHas('clinical_treatment_definitions', [
            'id' => $definition->id,
            'name' => 'Used Treatment',
        ]);

        // Verify it's NOT soft deleted from write model
        $this->assertDatabaseHas('treatment_definitions', [
            'id' => $definition->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * FASE 20.7: Test deletion removes treatment from catalog listing
     */
    public function test_deletion_removes_treatment_from_catalog_listing(): void
    {
        // Create two treatments
        $definition1 = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Treatment to Delete',
            'default_price' => 50.00,
        ]);

        $definition2 = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Treatment to Keep',
            'default_price' => 75.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Verify both appear in listing
        $listingBefore = $this->get(route('workspace.treatments.index'));
        $listingBefore->assertSee('Treatment to Delete');
        $listingBefore->assertSee('Treatment to Keep');

        // Delete first treatment
        $this->delete(route('workspace.treatment-definitions.destroy', ['treatmentDefinition' => $definition1->id]));

        // Verify only the second treatment appears in listing
        $listingAfter = $this->get(route('workspace.treatments.index'));
        $listingAfter->assertDontSee('Treatment to Delete');
        $listingAfter->assertSee('Treatment to Keep');
    }

    /**
     * FASE 20.7: Test usage count includes soft-deleted visits
     */
    public function test_usage_check_includes_soft_deleted_visits(): void
    {
        // Setup patient and visit
        $patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'dni' => '33333333C',
            'email' => 'alice@example.com',
            'phone' => '333333333',
        ]);

        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $patient->id,
            'occurred_at' => now(),
        ]);

        // Create treatment definition
        $definition = $this->catalogService->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Historical Treatment',
            'default_price' => 200.00,
        ]);

        $this->outboxConsumer->processPendingEvents();

        // Use treatment in visit
        $treatmentService = app(ClinicalTreatmentService::class);
        $visitTreatment = $treatmentService->addTreatmentToVisit($visit->id, [
            'treatment_definition_id' => $definition->id,
            'type' => 'Historical Treatment',
            'amount' => 200.00,
        ]);

        // Soft delete the visit treatment
        $visitTreatment->delete();

        // CRITICAL: Even though visit treatment is soft-deleted, it should STILL prevent deletion
        $response = $this->delete(route('workspace.treatment-definitions.destroy', ['treatmentDefinition' => $definition->id]));

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Cannot delete treatment definition: it has been used in 1 visit(s). You can only deactivate it.']);
    }
}
