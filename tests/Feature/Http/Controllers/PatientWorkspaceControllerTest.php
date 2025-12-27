<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\ClinicalVisit;
use App\Events\Clinical\VisitRecorded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PatientWorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

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

        app()->instance('currentClinicId', $this->clinic->id);
    }

    public function test_creates_visit_successfully(): void
    {
        Event::fake();

        $response = $this->post(route('workspace.patient.visits.store', ['patient' => $this->patient->id]), [
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'visit_type' => 'Primera visita',
            'summary' => 'Paciente refiere dolor',
            'professional_id' => null,
        ]);

        $response->assertStatus(200);

        // Verify visit was created in write model
        $this->assertDatabaseHas('visits', [
            'patient_id' => $this->patient->id,
            'visit_type' => 'Primera visita',
        ]);

        // Verify event was emitted
        Event::assertDispatched(VisitRecorded::class);

        // Verify projection was created
        $this->assertDatabaseHas('clinical_visits', [
            'patient_id' => $this->patient->id,
            'visit_type' => 'Primera visita',
        ]);
    }

    public function test_requires_occurred_at(): void
    {
        $response = $this->post(route('workspace.patient.visits.store', ['patient' => $this->patient->id]), [
            'visit_type' => 'Revisión',
            'summary' => 'Test',
        ]);

        $response->assertSessionHasErrors('occurred_at');
    }

    public function test_validates_date_format(): void
    {
        $response = $this->post(route('workspace.patient.visits.store', ['patient' => $this->patient->id]), [
            'occurred_at' => 'invalid-date',
            'visit_type' => 'Revisión',
        ]);

        $response->assertSessionHasErrors('occurred_at');
    }

    public function test_returns_404_for_nonexistent_patient(): void
    {
        $response = $this->post(route('workspace.patient.visits.store', ['patient' => 99999]), [
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(404);
    }

    public function test_creates_visit_without_optional_fields(): void
    {
        Event::fake();

        $response = $this->post(route('workspace.patient.visits.store', ['patient' => $this->patient->id]), [
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('visits', [
            'patient_id' => $this->patient->id,
            'visit_type' => null,
            'summary' => null,
            'professional_id' => null,
        ]);
    }

    public function test_returns_updated_visits_partial(): void
    {
        // Create an existing visit
        $existingVisit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now()->subDay(),
            'visit_type' => 'Revisión',
        ]);

        ClinicalVisit::create([
            'id' => $existingVisit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $existingVisit->occurred_at,
            'professional_id' => null,
            'visit_type' => 'Revisión',
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $response = $this->post(route('workspace.patient.visits.store', ['patient' => $this->patient->id]), [
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'visit_type' => 'Nueva visita',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('workspace.patient.partials._visits_content');
        $response->assertViewHas('clinicalVisits');
        $response->assertViewHas('visitsMeta');
    }

    // FASE 20.3: Tests for adding treatments to visits

    public function test_adds_treatment_to_visit_successfully(): void
    {
        Event::fake();

        // Create a visit first
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'visit_type' => 'Primera visita',
        ]);

        // Create projection (would normally be created by VisitRecorded event)
        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'visit_type' => 'Primera visita',
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $response = $this->post(route('workspace.visit.treatments.store', ['visit' => $visit->id]), [
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
            'notes' => 'Composite clase II',
        ]);

        $response->assertStatus(200);

        // Verify treatment was created in write model
        $this->assertDatabaseHas('visit_treatments', [
            'visit_id' => $visit->id,
            'type' => 'Empaste',
            'tooth' => '16',
        ]);

        // Verify event was emitted
        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment.added',
        ]);
    }

    public function test_treatment_requires_type(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $response = $this->post(route('workspace.visit.treatments.store', ['visit' => $visit->id]), [
            'tooth' => '16',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_treatment_validates_amount_is_numeric(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $response = $this->post(route('workspace.visit.treatments.store', ['visit' => $visit->id]), [
            'type' => 'Empaste',
            'amount' => 'not-a-number',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_treatment_validates_amount_is_positive(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $response = $this->post(route('workspace.visit.treatments.store', ['visit' => $visit->id]), [
            'type' => 'Empaste',
            'amount' => '-50',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_creates_treatment_without_optional_fields(): void
    {
        Event::fake();

        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $response = $this->post(route('workspace.visit.treatments.store', ['visit' => $visit->id]), [
            'type' => 'Consulta',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('visit_treatments', [
            'visit_id' => $visit->id,
            'type' => 'Consulta',
            'tooth' => null,
            'amount' => null,
            'notes' => null,
        ]);
    }

    public function test_returns_updated_treatments_partial(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'visit_type' => 'Revisión',
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $response = $this->post(route('workspace.visit.treatments.store', ['visit' => $visit->id]), [
            'type' => 'Limpieza',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('workspace.patient.partials._visit_treatments');
        $response->assertViewHas('clinicalVisit');
    }

    // FASE 20.4: Tests for updating treatments

    public function test_updates_treatment_successfully(): void
    {
        // Create visit and treatment
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $treatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
            'notes' => 'Original notes',
        ]);

        // Create projection (would normally be created by TreatmentAdded event)
        \App\Models\ClinicalTreatment::create([
            'id' => $treatment->id,
            'clinic_id' => $this->clinic->id,
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
            'notes' => 'Original notes',
            'projected_at' => now(),
            'created_at' => now(),
        ]);

        $response = $this->patch(route('workspace.treatments.update', ['treatment' => $treatment->id]), [
            'type' => 'Endodoncia',
            'amount' => '150.00',
        ]);

        $response->assertStatus(200);

        // Verify treatment was updated in write model
        $this->assertDatabaseHas('visit_treatments', [
            'id' => $treatment->id,
            'type' => 'Endodoncia',
            'amount' => '150.00',
            'tooth' => '16', // not updated
            'notes' => 'Original notes', // not updated
        ]);

        // Verify event was emitted
        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment.updated',
        ]);
    }

    public function test_update_returns_updated_treatment_item(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
            'created_at' => now(),
        ]);

        $treatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
        ]);

        // Create projection (would normally be created by TreatmentAdded event)
        \App\Models\ClinicalTreatment::create([
            'id' => $treatment->id,
            'clinic_id' => $this->clinic->id,
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
            'projected_at' => now(),
            'created_at' => now(),
        ]);

        $response = $this->patch(route('workspace.treatments.update', ['treatment' => $treatment->id]), [
            'type' => 'Empaste mejorado',
        ]);

        $response->assertStatus(200);
        // Now returns only the single treatment item (outerHTML swap)
        $response->assertViewIs('workspace.patient.partials._visit_treatment_item');
        $response->assertViewHas('treatment');
        $response->assertViewHas('visitId');
    }

    public function test_update_validates_amount_is_numeric(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $treatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
        ]);

        $response = $this->patch(route('workspace.treatments.update', ['treatment' => $treatment->id]), [
            'amount' => 'not-a-number',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_update_validates_amount_is_positive(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $treatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
        ]);

        $response = $this->patch(route('workspace.treatments.update', ['treatment' => $treatment->id]), [
            'amount' => '-50',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_update_allows_partial_updates(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $treatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
            'notes' => 'Original',
        ]);

        // Create projection (would normally be created by TreatmentAdded event)
        \App\Models\ClinicalTreatment::create([
            'id' => $treatment->id,
            'clinic_id' => $this->clinic->id,
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
            'notes' => 'Original',
            'projected_at' => now(),
            'created_at' => now(),
        ]);

        // Update only notes
        $response = $this->patch(route('workspace.treatments.update', ['treatment' => $treatment->id]), [
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200);

        // Verify only notes was updated
        $this->assertDatabaseHas('visit_treatments', [
            'id' => $treatment->id,
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
            'notes' => 'Updated notes',
        ]);
    }

    // FASE 20.4: Tests for deleting treatments

    public function test_deletes_treatment_successfully(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 1,
            'projected_at' => now(),
        ]);

        $treatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
        ]);

        // Also create projection for the treatment
        \App\Models\ClinicalTreatment::create([
            'id' => $treatment->id,
            'clinic_id' => $this->clinic->id,
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
            'projected_at' => now(),
            'created_at' => now(),
        ]);

        $response = $this->delete(route('workspace.treatments.delete', ['treatment' => $treatment->id]));

        $response->assertStatus(200);

        // Verify treatment was soft deleted in write model
        $this->assertSoftDeleted('visit_treatments', [
            'id' => $treatment->id,
        ]);

        // Verify event was emitted
        $this->assertDatabaseHas('event_outbox', [
            'event_name' => 'clinical.treatment.removed',
        ]);
    }

    public function test_delete_returns_updated_treatments_partial(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 1,
            'projected_at' => now(),
        ]);

        $treatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
        ]);

        \App\Models\ClinicalTreatment::create([
            'id' => $treatment->id,
            'clinic_id' => $this->clinic->id,
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
            'projected_at' => now(),
            'created_at' => now(),
        ]);

        $response = $this->delete(route('workspace.treatments.delete', ['treatment' => $treatment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('workspace.patient.partials._visit_treatments');
        $response->assertViewHas('clinicalVisit');
    }

    public function test_delete_returns_404_for_nonexistent_treatment(): void
    {
        $response = $this->delete(route('workspace.treatments.delete', ['treatment' => 'non-existent-uuid']));

        $response->assertStatus(404);
    }

    public function test_delete_handles_already_deleted_treatment(): void
    {
        $visit = Visit::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
        ]);

        ClinicalVisit::create([
            'id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => $visit->occurred_at,
            'professional_id' => null,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $treatment = \App\Models\VisitTreatment::create([
            'visit_id' => $visit->id,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'type' => 'Empaste',
        ]);

        // Soft delete the treatment
        $treatment->delete();

        $response = $this->delete(route('workspace.treatments.delete', ['treatment' => $treatment->id]));

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Treatment already deleted']);
    }
}
