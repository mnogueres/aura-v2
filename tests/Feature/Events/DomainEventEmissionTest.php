<?php

namespace Tests\Feature\Events;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoiceIssued;
use App\Events\Billing\PaymentApplied;
use App\Events\Billing\PaymentRecorded;
use App\Events\Billing\PaymentUnlinked;
use App\Events\CRM\PatientCreated;
use App\Events\Platform\IdempotencyConflict;
use App\Events\Platform\IdempotencyReplayed;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class DomainEventEmissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function patient_created_event_is_emitted_once_on_successful_creation()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/patients', [
                'clinic_id' => $clinic->id,
                'first_name' => 'Ana',
                'last_name' => 'Test',
            ])
            ->assertStatus(201);

        Event::assertDispatched(PatientCreated::class, 1);
    }

    /** @test */
    public function patient_created_event_is_not_emitted_on_replay()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $key = (string) Str::uuid();
        $payload = [
            'clinic_id' => $clinic->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
        ];

        // First request
        $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/patients', $payload)
            ->assertStatus(201);

        Event::assertDispatched(PatientCreated::class, 1);

        // Replay - should NOT emit event again
        $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/patients', $payload)
            ->assertStatus(201);

        // Still only 1 event dispatched
        Event::assertDispatched(PatientCreated::class, 1);
    }

    /** @test */
    public function patient_created_event_is_not_emitted_on_validation_error()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/patients', [
                'clinic_id' => $clinic->id,
                // Missing required fields
            ])
            ->assertStatus(422);

        Event::assertNotDispatched(PatientCreated::class);
    }

    /** @test */
    public function invoice_created_event_is_emitted_on_successful_creation()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/invoices', [
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'invoice_number' => 'INV-001',
                'invoice_date' => '2025-12-24',
                'status' => 'draft',
            ])
            ->assertStatus(201);

        Event::assertDispatched(InvoiceCreated::class, 1);
    }

    /** @test */
    public function invoice_issued_event_is_emitted_when_created_as_issued()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/invoices', [
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'invoice_number' => 'INV-002',
                'invoice_date' => '2025-12-24',
                'status' => 'issued',
            ])
            ->assertStatus(201);

        Event::assertDispatched(InvoiceCreated::class, 1);
        Event::assertDispatched(InvoiceIssued::class, 1);
    }

    /** @test */
    public function payment_recorded_and_applied_events_are_emitted_with_invoice()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
        ]);

        $invoice = \App\Models\Invoice::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-003',
            'invoice_date' => '2025-12-24',
            'status' => 'issued',
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/payments', [
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'invoice_id' => $invoice->id,
                'amount' => 50.00,
                'payment_date' => '2025-12-24',
                'payment_method' => 'cash',
            ])
            ->assertStatus(201);

        Event::assertDispatched(PaymentRecorded::class, 1);
        Event::assertDispatched(PaymentApplied::class, 1);
        Event::assertNotDispatched(PaymentUnlinked::class);
    }

    /** @test */
    public function payment_recorded_and_unlinked_events_are_emitted_without_invoice()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/payments', [
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'invoice_id' => null,
                'amount' => 50.00,
                'payment_date' => '2025-12-24',
                'payment_method' => 'cash',
            ])
            ->assertStatus(201);

        Event::assertDispatched(PaymentRecorded::class, 1);
        Event::assertDispatched(PaymentUnlinked::class, 1);
        Event::assertNotDispatched(PaymentApplied::class);
    }

    /** @test */
    public function idempotency_replayed_event_is_emitted_on_replay()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $key = (string) Str::uuid();
        $payload = [
            'clinic_id' => $clinic->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
        ];

        // First request
        $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/patients', $payload)
            ->assertStatus(201);

        // Replay
        $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/patients', $payload)
            ->assertStatus(201);

        Event::assertDispatched(IdempotencyReplayed::class, 1);
    }

    /** @test */
    public function idempotency_conflict_event_is_emitted_on_conflict()
    {
        Event::fake();

        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $key = (string) Str::uuid();

        // First request
        $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/patients', [
                'clinic_id' => $clinic->id,
                'first_name' => 'Ana',
                'last_name' => 'Test',
            ])
            ->assertStatus(201);

        // Same key, different body
        $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/patients', [
                'clinic_id' => $clinic->id,
                'first_name' => 'Different',
                'last_name' => 'Name',
            ])
            ->assertStatus(409);

        Event::assertDispatched(IdempotencyConflict::class, 1);
    }
}
