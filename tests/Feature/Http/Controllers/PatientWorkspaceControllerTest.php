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
}
