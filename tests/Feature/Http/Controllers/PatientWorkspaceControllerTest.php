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
}
