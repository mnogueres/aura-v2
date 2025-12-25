<?php

namespace Tests\Feature\Workspace;

use App\Events\CRM\PatientCreated;
use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\PaymentRecorded;
use App\Events\Platform\IdempotencyReplayed;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use App\Models\PatientSummary;
use App\Models\PatientTimeline;
use App\Models\BillingTimeline;
use App\Models\AuditTrail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_patient_summary()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '12345678A',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $summary = PatientSummary::create([
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

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson("/api/v1/workspace/patients/{$patient->id}/summary");

        $response->assertStatus(200)
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.invoices_count', 5)
            ->assertJsonPath('data.payments_count', 3)
            ->assertJsonPath('data.total_invoiced_amount', '1000.00')
            ->assertJsonPath('data.total_paid_amount', '500.00');
    }

    /** @test */
    public function it_returns_404_when_patient_summary_not_found()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson('/api/v1/workspace/patients/999/summary');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'patient_summary_not_found');
    }

    /** @test */
    public function it_does_not_return_summary_from_another_clinic()
    {
        $clinic1 = Clinic::create(['name' => 'Clinic 1']);
        $clinic2 = Clinic::create(['name' => 'Clinic 2']);

        $user = User::factory()->create(['clinic_id' => $clinic1->id]);

        $patient = Patient::create([
            'clinic_id' => $clinic2->id,
            'dni' => '87654321B',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        PatientSummary::create([
            'clinic_id' => $clinic2->id,
            'patient_id' => $patient->id,
            'created_at_occurred' => now(),
            'last_activity_at' => now(),
            'invoices_count' => 5,
            'payments_count' => 3,
            'total_invoiced_amount' => 1000.00,
            'total_paid_amount' => 500.00,
            'projected_at' => now(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic1->id);

        $response = $this->getJson("/api/v1/workspace/patients/{$patient->id}/summary");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_patient_timeline_with_pagination()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '11111111A',
            'first_name' => 'Test',
            'last_name' => 'Patient',
        ]);

        // Create 30 timeline entries
        for ($i = 0; $i < 30; $i++) {
            PatientTimeline::create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'event_name' => 'crm.patient.created',
                'event_payload' => ['patient_id' => $patient->id],
                'occurred_at' => now()->addMinutes($i),
                'projected_at' => now(),
                'source_event_id' => (string) Str::uuid(),
            ]);
        }

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson("/api/v1/workspace/patients/{$patient->id}/timeline");

        $response->assertStatus(200)
            ->assertJsonCount(25, 'data')
            ->assertJsonPath('meta.pagination.total', 30)
            ->assertJsonPath('meta.pagination.per_page', 25)
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.last_page', 2);
    }

    /** @test */
    public function it_returns_patient_timeline_in_chronological_order()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '22222222A',
            'first_name' => 'Test',
            'last_name' => 'Patient',
        ]);

        $time1 = now()->subHours(2);
        $time2 = now()->subHours(1);
        $time3 = now();

        PatientTimeline::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'event.3',
            'event_payload' => [],
            'occurred_at' => $time3,
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        PatientTimeline::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'event.1',
            'event_payload' => [],
            'occurred_at' => $time1,
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        PatientTimeline::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'event.2',
            'event_payload' => [],
            'occurred_at' => $time2,
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson("/api/v1/workspace/patients/{$patient->id}/timeline");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('event.1', $data[0]['event_name']);
        $this->assertEquals('event.2', $data[1]['event_name']);
        $this->assertEquals('event.3', $data[2]['event_name']);
    }

    /** @test */
    public function it_does_not_return_timeline_from_another_clinic()
    {
        $clinic1 = Clinic::create(['name' => 'Clinic 1']);
        $clinic2 = Clinic::create(['name' => 'Clinic 2']);

        $user = User::factory()->create(['clinic_id' => $clinic1->id]);

        $patient1 = Patient::create([
            'clinic_id' => $clinic1->id,
            'dni' => '33333333A',
            'first_name' => 'Patient',
            'last_name' => 'One',
        ]);
        $patient2 = Patient::create([
            'clinic_id' => $clinic2->id,
            'dni' => '44444444A',
            'first_name' => 'Patient',
            'last_name' => 'Two',
        ]);

        PatientTimeline::create([
            'clinic_id' => $clinic1->id,
            'patient_id' => $patient1->id,
            'event_name' => 'event.clinic1',
            'event_payload' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        PatientTimeline::create([
            'clinic_id' => $clinic2->id,
            'patient_id' => $patient2->id,
            'event_name' => 'event.clinic2',
            'event_payload' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic1->id);

        $response = $this->getJson("/api/v1/workspace/patients/{$patient1->id}/timeline");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals('event.clinic1', $data[0]['event_name']);
    }

    /** @test */
    public function it_returns_billing_timeline_with_pagination()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '55555555A',
            'first_name' => 'Test',
            'last_name' => 'Patient',
        ]);

        for ($i = 0; $i < 15; $i++) {
            BillingTimeline::create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'event_name' => 'billing.invoice.created',
                'amount' => 100.00,
                'currency' => 'USD',
                'reference_id' => $i + 1,
                'event_payload' => [],
                'occurred_at' => now()->addMinutes($i),
                'projected_at' => now(),
                'source_event_id' => (string) Str::uuid(),
            ]);
        }

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson("/api/v1/workspace/patients/{$patient->id}/billing");

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.pagination.total', 15)
            ->assertJsonPath('meta.pagination.per_page', 25);
    }

    /** @test */
    public function it_returns_billing_timeline_in_chronological_order()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '66666666A',
            'first_name' => 'Test',
            'last_name' => 'Patient',
        ]);

        $time1 = now()->subHours(2);
        $time2 = now()->subHours(1);

        BillingTimeline::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'billing.payment.recorded',
            'amount' => 50.00,
            'currency' => 'USD',
            'reference_id' => 2,
            'event_payload' => [],
            'occurred_at' => $time2,
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        BillingTimeline::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'event_name' => 'billing.invoice.created',
            'amount' => 100.00,
            'currency' => 'USD',
            'reference_id' => 1,
            'event_payload' => [],
            'occurred_at' => $time1,
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson("/api/v1/workspace/patients/{$patient->id}/billing");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('billing.invoice.created', $data[0]['event_name']);
        $this->assertEquals('billing.payment.recorded', $data[1]['event_name']);
    }

    /** @test */
    public function it_does_not_return_billing_timeline_from_another_clinic()
    {
        $clinic1 = Clinic::create(['name' => 'Clinic 1']);
        $clinic2 = Clinic::create(['name' => 'Clinic 2']);

        $user = User::factory()->create(['clinic_id' => $clinic1->id]);

        $patient1 = Patient::create([
            'clinic_id' => $clinic1->id,
            'dni' => '77777777A',
            'first_name' => 'Patient',
            'last_name' => 'One',
        ]);
        $patient2 = Patient::create([
            'clinic_id' => $clinic2->id,
            'dni' => '88888888A',
            'first_name' => 'Patient',
            'last_name' => 'Two',
        ]);

        BillingTimeline::create([
            'clinic_id' => $clinic1->id,
            'patient_id' => $patient1->id,
            'event_name' => 'billing.invoice.created',
            'amount' => 100.00,
            'currency' => 'USD',
            'reference_id' => 1,
            'event_payload' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        BillingTimeline::create([
            'clinic_id' => $clinic2->id,
            'patient_id' => $patient2->id,
            'event_name' => 'billing.invoice.created',
            'amount' => 200.00,
            'currency' => 'USD',
            'reference_id' => 2,
            'event_payload' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic1->id);

        $response = $this->getJson("/api/v1/workspace/patients/{$patient1->id}/billing");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals(100.00, floatval($data[0]['amount']));
    }

    /** @test */
    public function it_returns_audit_trail_with_pagination()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        for ($i = 0; $i < 30; $i++) {
            AuditTrail::create([
                'clinic_id' => $clinic->id,
                'event_name' => 'platform.rate_limited',
                'category' => 'security',
                'severity' => 'warning',
                'actor_type' => 'system',
                'actor_id' => null,
                'context' => [],
                'occurred_at' => now()->addMinutes($i),
                'projected_at' => now(),
                'source_event_id' => (string) Str::uuid(),
            ]);
        }

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson('/api/v1/workspace/audit');

        $response->assertStatus(200)
            ->assertJsonCount(25, 'data')
            ->assertJsonPath('meta.pagination.total', 30)
            ->assertJsonPath('meta.pagination.per_page', 25);
    }

    /** @test */
    public function it_returns_audit_trail_in_reverse_chronological_order()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $time1 = now()->subHours(2);
        $time2 = now()->subHours(1);
        $time3 = now();

        AuditTrail::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'event.1',
            'category' => 'security',
            'severity' => 'info',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => $time1,
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        AuditTrail::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'event.2',
            'category' => 'security',
            'severity' => 'warning',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => $time2,
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        AuditTrail::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'event.3',
            'category' => 'security',
            'severity' => 'error',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => $time3,
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson('/api/v1/workspace/audit');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('event.3', $data[0]['event_name']);
        $this->assertEquals('event.2', $data[1]['event_name']);
        $this->assertEquals('event.1', $data[2]['event_name']);
    }

    /** @test */
    public function it_filters_audit_trail_by_severity()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        AuditTrail::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'event.1',
            'category' => 'security',
            'severity' => 'info',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        AuditTrail::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'event.2',
            'category' => 'security',
            'severity' => 'error',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson('/api/v1/workspace/audit?severity=error');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals('error', $data[0]['severity']);
    }

    /** @test */
    public function it_filters_audit_trail_by_category()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        AuditTrail::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'event.1',
            'category' => 'security',
            'severity' => 'info',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        AuditTrail::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'event.2',
            'category' => 'platform',
            'severity' => 'info',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->getJson('/api/v1/workspace/audit?category=platform');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals('platform', $data[0]['category']);
    }

    /** @test */
    public function it_does_not_return_audit_trail_from_another_clinic()
    {
        $clinic1 = Clinic::create(['name' => 'Clinic 1']);
        $clinic2 = Clinic::create(['name' => 'Clinic 2']);

        $user = User::factory()->create(['clinic_id' => $clinic1->id]);

        AuditTrail::create([
            'clinic_id' => $clinic1->id,
            'event_name' => 'event.clinic1',
            'category' => 'security',
            'severity' => 'info',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        AuditTrail::create([
            'clinic_id' => $clinic2->id,
            'event_name' => 'event.clinic2',
            'category' => 'security',
            'severity' => 'info',
            'actor_type' => 'system',
            'actor_id' => null,
            'context' => [],
            'occurred_at' => now(),
            'projected_at' => now(),
            'source_event_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic1->id);

        $response = $this->getJson('/api/v1/workspace/audit');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals('event.clinic1', $data[0]['event_name']);
    }

    /** @test */
    public function it_does_not_emit_events_on_workspace_reads()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '99999999A',
            'first_name' => 'Test',
            'last_name' => 'Patient',
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

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $this->getJson("/api/v1/workspace/patients/{$patient->id}/summary");
        $this->getJson("/api/v1/workspace/patients/{$patient->id}/timeline");
        $this->getJson("/api/v1/workspace/patients/{$patient->id}/billing");
        $this->getJson('/api/v1/workspace/audit');

        Event::assertNotDispatched(PatientCreated::class);
        Event::assertNotDispatched(InvoiceCreated::class);
        Event::assertNotDispatched(PaymentRecorded::class);
        Event::assertNotDispatched(IdempotencyReplayed::class);
    }

    /** @test */
    public function it_does_not_write_to_domain_tables()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'dni' => '00000000A',
            'first_name' => 'Test',
            'last_name' => 'Patient',
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

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $initialPatientCount = Patient::count();

        $this->getJson("/api/v1/workspace/patients/{$patient->id}/summary");
        $this->getJson("/api/v1/workspace/patients/{$patient->id}/timeline");
        $this->getJson("/api/v1/workspace/patients/{$patient->id}/billing");
        $this->getJson('/api/v1/workspace/audit');

        $this->assertEquals($initialPatientCount, Patient::count());
    }
}
