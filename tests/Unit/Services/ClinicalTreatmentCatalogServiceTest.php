<?php

namespace Tests\Unit\Services;

use App\Services\ClinicalTreatmentCatalogService;
use App\Services\EventService;
use App\Models\Clinic;
use App\Models\TreatmentDefinition;
use App\Models\EventOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 20.5: Unit tests for ClinicalTreatmentCatalogService
 */
class ClinicalTreatmentCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClinicalTreatmentCatalogService $service;
    private Clinic $clinic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ClinicalTreatmentCatalogService::class);

        $this->clinic = Clinic::create([
            'name' => 'Test Clinic',
            'dni' => '12345678A',
            'address' => 'Test Address',
            'phone' => '123456789',
        ]);
    }

    // CREATE TESTS

    public function test_creates_treatment_definition_successfully(): void
    {
        $data = [
            'clinic_id' => $this->clinic->id,
            'name' => 'Empaste Composite',
            'default_price' => 65.00,
        ];

        $definition = $this->service->createTreatmentDefinition($data);

        $this->assertInstanceOf(TreatmentDefinition::class, $definition);
        $this->assertEquals('Empaste Composite', $definition->name);
        $this->assertEquals(65.00, $definition->default_price);
        $this->assertTrue($definition->active);
        $this->assertEquals($this->clinic->id, $definition->clinic_id);
    }

    public function test_creates_treatment_definition_without_price(): void
    {
        $data = [
            'clinic_id' => $this->clinic->id,
            'name' => 'Consulta General',
        ];

        $definition = $this->service->createTreatmentDefinition($data);

        $this->assertEquals('Consulta General', $definition->name);
        $this->assertNull($definition->default_price);
        $this->assertTrue($definition->active);
    }

    public function test_create_emits_treatment_definition_created_event(): void
    {
        $data = [
            'clinic_id' => $this->clinic->id,
            'name' => 'Limpieza Dental',
            'default_price' => 40.00,
        ];

        $definition = $this->service->createTreatmentDefinition($data);

        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment_definition.created',
            'clinic_id' => $this->clinic->id,
            'status' => 'pending',
        ]);

        $outboxEvent = EventOutbox::where('event_name', 'clinical.treatment_definition.created')->first();
        $this->assertNotNull($outboxEvent);
        $this->assertEquals($this->clinic->id, $outboxEvent->payload['clinic_id']);
        $this->assertEquals($definition->id, $outboxEvent->payload['treatment_definition_id']);
        $this->assertEquals('Limpieza Dental', $outboxEvent->payload['name']);
        $this->assertEquals(40.00, $outboxEvent->payload['default_price']);
    }

    public function test_create_requires_name(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('name is required');

        $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
        ]);
    }

    public function test_create_validates_default_price_is_numeric(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('default_price must be numeric');

        $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test Treatment',
            'default_price' => 'not-a-number',
        ]);
    }

    public function test_create_validates_default_price_is_positive(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('default_price must be >= 0');

        $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test Treatment',
            'default_price' => -50.00,
        ]);
    }

    public function test_treatment_definition_is_persisted_in_database(): void
    {
        $data = [
            'clinic_id' => $this->clinic->id,
            'name' => 'Endodoncia',
            'default_price' => 150.00,
        ];

        $definition = $this->service->createTreatmentDefinition($data);

        $this->assertDatabaseHas('treatment_definitions', [
            'id' => $definition->id,
            'clinic_id' => $this->clinic->id,
            'name' => 'Endodoncia',
            'default_price' => 150.00,
            'active' => true,
        ]);
    }

    // UPDATE TESTS

    public function test_updates_treatment_definition_successfully(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Empaste Original',
            'default_price' => 50.00,
        ]);

        $updated = $this->service->updateTreatmentDefinition($definition->id, [
            'name' => 'Empaste Premium',
            'default_price' => 75.00,
        ]);

        $this->assertEquals('Empaste Premium', $updated->name);
        $this->assertEquals(75.00, $updated->default_price);
    }

    public function test_update_partial_fields_only(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Original Name',
            'default_price' => 50.00,
        ]);

        $updated = $this->service->updateTreatmentDefinition($definition->id, [
            'default_price' => 60.00,
        ]);

        $this->assertEquals('Original Name', $updated->name); // not updated
        $this->assertEquals(60.00, $updated->default_price);
    }

    public function test_update_emits_treatment_definition_updated_event(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test',
            'default_price' => 50.00,
        ]);

        $this->service->updateTreatmentDefinition($definition->id, [
            'name' => 'Updated Test',
        ]);

        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment_definition.updated',
        ]);
    }

    public function test_update_requires_name_not_empty(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('name cannot be empty');

        $this->service->updateTreatmentDefinition($definition->id, [
            'name' => '',
        ]);
    }

    public function test_update_validates_default_price_is_numeric(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('default_price must be numeric');

        $this->service->updateTreatmentDefinition($definition->id, [
            'default_price' => 'not-a-number',
        ]);
    }

    public function test_update_throws_exception_for_nonexistent_definition(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment definition not found');

        $this->service->updateTreatmentDefinition('non-existent-uuid', [
            'name' => 'Test',
        ]);
    }

    public function test_update_throws_exception_for_soft_deleted_definition(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test',
        ]);

        $definition->delete(); // soft delete

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot update deleted treatment definition');

        $this->service->updateTreatmentDefinition($definition->id, [
            'name' => 'Updated',
        ]);
    }

    // DEACTIVATE TESTS

    public function test_deactivates_treatment_definition_successfully(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test',
        ]);

        $this->service->deactivateTreatmentDefinition($definition->id);

        $definition->refresh();
        $this->assertFalse($definition->active);
    }

    public function test_deactivate_emits_treatment_definition_deactivated_event(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test',
        ]);

        $this->service->deactivateTreatmentDefinition($definition->id);

        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment_definition.deactivated',
        ]);
    }

    public function test_deactivate_throws_exception_for_nonexistent_definition(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment definition not found');

        $this->service->deactivateTreatmentDefinition('non-existent-uuid');
    }

    public function test_deactivate_throws_exception_for_already_inactive_definition(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test',
        ]);

        $this->service->deactivateTreatmentDefinition($definition->id);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment definition already inactive');

        $this->service->deactivateTreatmentDefinition($definition->id);
    }

    // DELETE TESTS (FASE 20.7)

    public function test_deletes_treatment_definition_when_never_used(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test Treatment',
            'default_price' => 50.00,
        ]);

        $this->service->deleteTreatmentDefinition($definition->id);

        // Should be soft deleted
        $this->assertSoftDeleted('treatment_definitions', [
            'id' => $definition->id,
        ]);
    }

    public function test_delete_emits_treatment_definition_deleted_event(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test Treatment',
        ]);

        $this->service->deleteTreatmentDefinition($definition->id);

        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment_definition.deleted',
        ]);

        $outboxEvent = EventOutbox::where('event_name', 'clinical.treatment_definition.deleted')->first();
        $this->assertNotNull($outboxEvent);
        $this->assertEquals($this->clinic->id, $outboxEvent->payload['clinic_id']);
        $this->assertEquals($definition->id, $outboxEvent->payload['treatment_definition_id']);
    }

    public function test_cannot_delete_treatment_definition_with_usage(): void
    {
        // Create treatment definition
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Used Treatment',
            'default_price' => 100.00,
        ]);

        // Create a patient and visit for testing
        $patient = \App\Models\Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'dni' => '11111111A',
        ]);

        $visit = \App\Models\Visit::create([
            'patient_id' => $patient->id,
            'clinic_id' => $this->clinic->id,
            'occurred_at' => now(),
            'notes' => 'Test visit',
        ]);

        // Create a VisitTreatment using this definition
        \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'clinic_id' => $this->clinic->id,
            'treatment_definition_id' => $definition->id,
            'type' => 'Used Treatment',
            'amount' => 100.00,
        ]);

        // Attempt to delete should throw exception
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot delete treatment definition: it has been used in 1 visit(s)');

        $this->service->deleteTreatmentDefinition($definition->id);
    }

    public function test_delete_throws_exception_for_nonexistent_definition(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment definition not found');

        $this->service->deleteTreatmentDefinition('non-existent-uuid');
    }

    public function test_delete_throws_exception_for_already_deleted_definition(): void
    {
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test',
        ]);

        $this->service->deleteTreatmentDefinition($definition->id);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Treatment definition already deleted');

        $this->service->deleteTreatmentDefinition($definition->id);
    }

    public function test_cannot_delete_even_if_visit_treatment_is_soft_deleted(): void
    {
        // Create treatment definition
        $definition = $this->service->createTreatmentDefinition([
            'clinic_id' => $this->clinic->id,
            'name' => 'Test Treatment',
            'default_price' => 50.00,
        ]);

        // Create patient and visit
        $patient = \App\Models\Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'dni' => '22222222B',
        ]);

        $visit = \App\Models\Visit::create([
            'patient_id' => $patient->id,
            'clinic_id' => $this->clinic->id,
            'occurred_at' => now(),
            'notes' => 'Test visit',
        ]);

        // Create VisitTreatment and then soft delete it
        $visitTreatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'clinic_id' => $this->clinic->id,
            'treatment_definition_id' => $definition->id,
            'type' => 'Test Treatment',
            'amount' => 50.00,
        ]);

        $visitTreatment->delete(); // soft delete

        // Should still throw exception because historical data exists
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot delete treatment definition: it has been used in 1 visit(s)');

        $this->service->deleteTreatmentDefinition($definition->id);
    }
}
