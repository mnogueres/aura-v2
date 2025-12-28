<?php

namespace Tests\Feature\Clinical;

use App\Events\Clinical\VisitRecorded;
use App\Events\Clinical\TreatmentRecorded;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\ClinicalVisit;
use App\Models\ClinicalTreatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClinicalProjectionTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::create([
            'name' => 'Test Clinic',
            'email' => 'test@clinic.com',
        ]);

        $this->patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'dni' => '12345678A',
            'email' => 'patient@test.com',
        ]);
    }

    /** @test */
    public function it_projects_a_visit_when_visit_recorded_event_is_emitted()
    {
        // Create a professional first (FASE 21)
        $professional = \App\Models\Professional::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. García',
            'role' => 'dentist',
            'active' => true,
        ]);

        $visitId = (string) Str::uuid();
        $occurredAt = now()->toISOString();

        $event = new VisitRecorded(
            clinic_id: $this->clinic->id,
            visit_id: $visitId,
            patient_id: $this->patient->id,
            professional_id: $professional->id,  // UUID, not name
            occurred_at: $occurredAt,
            visit_type: 'Primera visita',
            summary: 'Paciente refiere dolor en molar',
            request_id: (string) Str::uuid(),
            user_id: 1
        );

        event($event);

        $this->assertDatabaseHas('clinical_visits', [
            'id' => $visitId,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'professional_id' => $professional->id,
            'visit_type' => 'Primera visita',
            'summary' => 'Paciente refiere dolor en molar',
        ]);
    }

    /** @test */
    public function it_projects_a_treatment_when_treatment_recorded_event_is_emitted()
    {
        // Create professional (FASE 21)
        $professional = \App\Models\Professional::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. García',
            'role' => 'dentist',
            'active' => true,
        ]);

        // First create a visit
        $visitId = (string) Str::uuid();
        ClinicalVisit::create([
            'id' => $visitId,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'professional_id' => $professional->id,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        // Then record a treatment
        $treatmentId = (string) Str::uuid();

        $event = new TreatmentRecorded(
            treatment_id: $treatmentId,
            visit_id: $visitId,
            patient_id: $this->patient->id,
            type: 'Empaste',
            tooth: '16',
            amount: '65.00',
            notes: 'Composite fotopolimerizable',
            request_id: (string) Str::uuid(),
            user_id: 1,
            clinic_id: $this->clinic->id
        );

        event($event);

        $this->assertDatabaseHas('clinical_treatments', [
            'id' => $treatmentId,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $visitId,
            'type' => 'Empaste',
            'tooth' => '16',
            'amount' => '65.00',
        ]);

        // Verify visit treatments_count was incremented
        $visit = ClinicalVisit::find($visitId);
        $this->assertEquals(1, $visit->treatments_count);
    }

    /** @test */
    public function it_respects_clinic_scoping_in_visits()
    {
        $otherClinic = Clinic::create([
            'name' => 'Other Clinic',
            'email' => 'other@clinic.com',
        ]);

        // Create professional for other clinic
        $otherProfessional = \App\Models\Professional::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $otherClinic->id,
            'name' => 'Dr. Otro',
            'role' => 'dentist',
            'active' => true,
        ]);

        $visitId = (string) Str::uuid();

        ClinicalVisit::create([
            'id' => $visitId,
            'clinic_id' => $otherClinic->id,
            'patient_id' => 999,
            'occurred_at' => now(),
            'professional_id' => $otherProfessional->id,
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        $repository = app(\App\Repositories\ClinicalVisitRepository::class);
        $visits = $repository->getVisitsForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(0, $visits);
    }

    /** @test */
    public function it_loads_treatments_for_a_visit()
    {
        // Create professional
        $professional = \App\Models\Professional::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. García',
            'role' => 'dentist',
            'active' => true,
        ]);

        $visitId = (string) Str::uuid();

        ClinicalVisit::create([
            'id' => $visitId,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'professional_id' => $professional->id,
            'treatments_count' => 2,
            'projected_at' => now(),
        ]);

        ClinicalTreatment::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $visitId,
            'type' => 'Empaste',
            'tooth' => '16',
            'projected_at' => now(),
            'created_at' => now(),
        ]);

        ClinicalTreatment::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $visitId,
            'type' => 'Limpieza',
            'projected_at' => now(),
            'created_at' => now(),
        ]);

        $repository = app(\App\Repositories\ClinicalTreatmentRepository::class);
        $treatments = $repository->getTreatmentsForVisit($visitId);

        $this->assertCount(2, $treatments);
    }

    /** @test */
    public function it_does_not_show_technical_events_in_workspace()
    {
        // This test verifies that the workspace shows clinical language, not technical events
        // Create professional
        $professional = \App\Models\Professional::create([
            'id' => (string) Str::uuid(),
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. García',
            'role' => 'dentist',
            'active' => true,
        ]);

        $visitId = (string) Str::uuid();

        $visit = ClinicalVisit::create([
            'id' => $visitId,
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'occurred_at' => now(),
            'professional_id' => $professional->id,
            'summary' => 'Visita de revisión',
            'treatments_count' => 0,
            'projected_at' => now(),
        ]);

        // The visit should have human-readable fields, not technical event names
        $this->assertNotEmpty($visit->professional_id);
        $this->assertNotContains('clinical.visit.recorded', $visit->toArray());
        $this->assertNotContains('event_name', array_keys($visit->toArray()));
    }
}
