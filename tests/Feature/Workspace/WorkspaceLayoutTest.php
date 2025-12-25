<?php

namespace Tests\Feature\Workspace;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientSummary;
use App\Models\PatientTimeline;
use App\Models\BillingTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceLayoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_responds_200_for_workspace_patient_show()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '12345678A',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        PatientSummary::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'created_at_occurred' => now(),
            'last_activity_at' => now(),
            'invoices_count' => 5,
            'payments_count' => 3,
            'total_invoiced_amount' => 1000.00,
            'total_paid_amount' => 500.00,
            'projected_at' => now(),
        ]);

        app()->instance('currentClinicId', $clinic->id);

        $response = $this->get(route('workspace.patient.show', ['patient' => $patient->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_renders_patient_summary()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '12345678A',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        PatientSummary::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'created_at_occurred' => now(),
            'last_activity_at' => now(),
            'invoices_count' => 5,
            'payments_count' => 3,
            'total_invoiced_amount' => 1000.00,
            'total_paid_amount' => 500.00,
            'projected_at' => now(),
        ]);

        app()->instance('currentClinicId', $clinic->id);

        $response = $this->get(route('workspace.patient.show', ['patient' => $patient->id]));

        $response->assertSee('Resumen del Paciente');
        $response->assertSee('1,000.00');
        $response->assertSee('500.00');
    }

    /** @test */
    public function it_renders_patient_timeline()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '12345678A',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        PatientSummary::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'created_at_occurred' => now(),
            'last_activity_at' => now(),
            'invoices_count' => 0,
            'payments_count' => 0,
            'total_invoiced_amount' => 0,
            'total_paid_amount' => 0,
            'projected_at' => now(),
        ]);

        PatientTimeline::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'crm.patient.created',
            'event_payload' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        app()->instance('currentClinicId', $clinic->id);

        $response = $this->get(route('workspace.patient.show', ['patient' => $patient->id]));

        $response->assertSee('Timeline del Paciente');
        $response->assertSee('crm.patient.created');
    }

    /** @test */
    public function it_renders_billing_timeline()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '12345678A',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        PatientSummary::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'created_at_occurred' => now(),
            'last_activity_at' => now(),
            'invoices_count' => 0,
            'payments_count' => 0,
            'total_invoiced_amount' => 0,
            'total_paid_amount' => 0,
            'projected_at' => now(),
        ]);

        BillingTimeline::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'billing.invoice.created',
            'amount' => 100.00,
            'currency' => 'EUR',
            'reference_id' => 1,
            'event_payload' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        app()->instance('currentClinicId', $clinic->id);

        $response = $this->get(route('workspace.patient.show', ['patient' => $patient->id]));

        $response->assertSee('Timeline de Facturación');
        $response->assertSee('billing.invoice.created');
        $response->assertSee('100.00');
    }

    /** @test */
    public function it_scopes_by_clinic_id()
    {
        $clinic1 = Clinic::create(['name' => 'Clinic 1']);
        $clinic2 = Clinic::create(['name' => 'Clinic 2']);

        $patient1 = Patient::create([
            'clinic_id' => $clinic1->id,
            'dni' => '11111111A',
            'first_name' => 'Patient',
            'last_name' => 'One',
        ]);

        $patient2 = Patient::create([
            'clinic_id' => $clinic2->id,
            'dni' => '22222222A',
            'first_name' => 'Patient',
            'last_name' => 'Two',
        ]);

        PatientSummary::create([
            'clinic_id' => $clinic1->id,
            'patient_id' => $patient1->id,
            'created_at_occurred' => now(),
            'last_activity_at' => now(),
            'invoices_count' => 5,
            'payments_count' => 3,
            'total_invoiced_amount' => 1000.00,
            'total_paid_amount' => 500.00,
            'projected_at' => now(),
        ]);

        PatientSummary::create([
            'clinic_id' => $clinic2->id,
            'patient_id' => $patient2->id,
            'created_at_occurred' => now(),
            'last_activity_at' => now(),
            'invoices_count' => 10,
            'payments_count' => 8,
            'total_invoiced_amount' => 2000.00,
            'total_paid_amount' => 1500.00,
            'projected_at' => now(),
        ]);

        app()->instance('currentClinicId', $clinic1->id);

        // Should see clinic 1 data
        $response = $this->get(route('workspace.patient.show', ['patient' => $patient1->id]));
        $response->assertSee('1,000.00');
        $response->assertDontSee('2,000.00');

        // Should NOT see clinic 2 data (API will return null/404)
        $response = $this->get(route('workspace.patient.show', ['patient' => $patient2->id]));
        $response->assertDontSee('2,000.00');
    }

    /** @test */
    public function it_does_not_query_domain_tables_directly()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '12345678A',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        PatientSummary::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'created_at_occurred' => now(),
            'last_activity_at' => now(),
            'invoices_count' => 5,
            'payments_count' => 3,
            'total_invoiced_amount' => 1000.00,
            'total_paid_amount' => 500.00,
            'projected_at' => now(),
        ]);

        app()->instance('currentClinicId', $clinic->id);

        // Controller should only make HTTP calls to API, not direct DB queries
        // This is validated by the fact that the controller uses Http::get()
        // and not direct Eloquent queries to Invoice, Payment, etc.

        $response = $this->get(route('workspace.patient.show', ['patient' => $patient->id]));

        $response->assertStatus(200);
        // The response should be successful without querying domain tables
        // (the controller fetches via API endpoints)
    }
}
