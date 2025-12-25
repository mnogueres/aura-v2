<?php

namespace Tests\Feature\Outbox;

use App\Events\CRM\PatientCreated;
use App\Models\Clinic;
use App\Models\EventOutbox;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventOutboxTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function event_is_persisted_to_outbox_exactly_once_on_successful_creation()
    {
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

        // Verify event was persisted to outbox exactly once
        $this->assertDatabaseCount('event_outbox', 1);

        $outboxEvent = EventOutbox::first();
        $this->assertEquals('crm.patient.created', $outboxEvent->event_name);
        $this->assertEquals('pending', $outboxEvent->status);
        $this->assertEquals(0, $outboxEvent->attempts);
    }

    /** @test */
    public function event_is_not_persisted_if_transaction_fails()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        try {
            DB::transaction(function () use ($clinic) {
                $patient = Patient::create([
                    'clinic_id' => $clinic->id,
                    'first_name' => 'Test',
                    'last_name' => 'Patient',
                ]);

                // Emit event manually to simulate service call
                app(\App\Services\EventService::class)->emit(
                    new PatientCreated($patient->id)
                );

                // Force transaction to fail
                throw new \Exception('Simulated transaction failure');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify event was NOT persisted because transaction rolled back
        $this->assertDatabaseCount('event_outbox', 0);
    }

    /** @test */
    public function event_is_not_persisted_on_validation_error()
    {
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

        // Verify event was NOT persisted
        $this->assertDatabaseCount('event_outbox', 0);
    }

    /** @test */
    public function event_payload_is_persisted_intact()
    {
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

        $outboxEvent = EventOutbox::first();

        // Verify payload structure
        $this->assertIsArray($outboxEvent->payload);
        $this->assertArrayHasKey('patient_id', $outboxEvent->payload);

        // Verify patient_id matches created patient
        $patient = Patient::first();
        $this->assertEquals($patient->id, $outboxEvent->payload['patient_id']);
    }

    /** @test */
    public function event_contains_clinic_id()
    {
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

        $outboxEvent = EventOutbox::first();

        // Verify clinic_id is persisted
        $this->assertNotNull($outboxEvent->clinic_id);
        $this->assertEquals($clinic->id, $outboxEvent->clinic_id);
    }

    /** @test */
    public function event_is_not_persisted_again_on_idempotent_replay()
    {
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

        $this->assertDatabaseCount('event_outbox', 1);

        // Replay - should return cached response without creating new event
        $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/patients', $payload)
            ->assertStatus(201);

        // Verify event was NOT persisted again
        $this->assertDatabaseCount('event_outbox', 1);
    }

    /** @test */
    public function event_has_occurred_at_and_recorded_at_timestamps()
    {
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

        $outboxEvent = EventOutbox::first();

        $this->assertNotNull($outboxEvent->occurred_at);
        $this->assertNotNull($outboxEvent->recorded_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $outboxEvent->occurred_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $outboxEvent->recorded_at);
    }

    /** @test */
    public function outbox_repository_can_retrieve_pending_events()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);

        // Create some outbox events
        EventOutbox::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'crm.patient.created',
            'payload' => ['patient_id' => 1],
            'occurred_at' => now(),
            'recorded_at' => now(),
            'status' => 'pending',
        ]);

        EventOutbox::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'crm.patient.created',
            'payload' => ['patient_id' => 2],
            'occurred_at' => now(),
            'recorded_at' => now(),
            'status' => 'processed',
        ]);

        $repository = app(\App\Contracts\OutboxRepositoryInterface::class);
        $pending = $repository->getPending();

        $this->assertCount(1, $pending);
        $this->assertEquals('pending', $pending->first()->status);
    }

    /** @test */
    public function outbox_repository_can_retrieve_failed_events()
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);

        // Create some outbox events
        EventOutbox::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'crm.patient.created',
            'payload' => ['patient_id' => 1],
            'occurred_at' => now(),
            'recorded_at' => now(),
            'status' => 'failed',
            'attempts' => 3,
            'last_error' => 'Test error',
        ]);

        EventOutbox::create([
            'clinic_id' => $clinic->id,
            'event_name' => 'crm.patient.created',
            'payload' => ['patient_id' => 2],
            'occurred_at' => now(),
            'recorded_at' => now(),
            'status' => 'pending',
        ]);

        $repository = app(\App\Contracts\OutboxRepositoryInterface::class);
        $failed = $repository->getFailed();

        $this->assertCount(1, $failed);
        $this->assertEquals('failed', $failed->first()->status);
        $this->assertEquals('Test error', $failed->first()->last_error);
    }
}
