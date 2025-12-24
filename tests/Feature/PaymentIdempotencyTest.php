<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\IdempotencyKey;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function createTestData(): array
    {
        $clinic = Clinic::create(['name' => 'Test Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dni' => '12345678A',
            'email' => 'john@example.com',
        ]);

        return compact('clinic', 'user', 'patient');
    }

    private function paymentPayload(int $clinicId, int $patientId, float $amount = 100.00): array
    {
        return [
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'amount' => $amount,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'notes' => 'Test payment',
        ];
    }

    public function test_retry_safe_same_request_creates_only_one_payment(): void
    {
        ['user' => $user, 'clinic' => $clinic, 'patient' => $patient] = $this->createTestData();
        app()->instance('currentClinicId', $clinic->id);

        $idempotencyKey = (string) Str::uuid();
        $payload = $this->paymentPayload($clinic->id, $patient->id);

        $response1 = $this->actingAs($user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/payments', $payload);

        $response1->assertStatus(201);

        $response2 = $this->actingAs($user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/payments', $payload);

        $response2->assertStatus(201);
        $this->assertEquals($response1->json('data.id'), $response2->json('data.id'));
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_same_key_with_different_body_returns_409_conflict(): void
    {
        ['user' => $user, 'clinic' => $clinic, 'patient' => $patient] = $this->createTestData();
        app()->instance('currentClinicId', $clinic->id);

        $idempotencyKey = (string) Str::uuid();

        $response1 = $this->actingAs($user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/payments', $this->paymentPayload($clinic->id, $patient->id, 100.00));

        $response1->assertStatus(201);

        $response2 = $this->actingAs($user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/payments', $this->paymentPayload($clinic->id, $patient->id, 200.00));

        $response2->assertStatus(409);
        $response2->assertJsonStructure([
            'data',
            'error' => ['code', 'message', 'details'],
            'meta',
        ]);
        $response2->assertJson([
            'error' => [
                'code' => 'IDEMPOTENCY_KEY_MISMATCH',
            ],
        ]);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_missing_idempotency_key_returns_400_bad_request(): void
    {
        ['user' => $user, 'clinic' => $clinic, 'patient' => $patient] = $this->createTestData();
        app()->instance('currentClinicId', $clinic->id);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments', $this->paymentPayload($clinic->id, $patient->id));

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'data',
            'error' => ['code', 'message'],
            'meta',
        ]);
        $response->assertJson([
            'error' => [
                'code' => 'IDEMPOTENCY_KEY_REQUIRED',
            ],
        ]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_expired_key_allows_new_execution(): void
    {
        ['user' => $user, 'clinic' => $clinic, 'patient' => $patient] = $this->createTestData();
        app()->instance('currentClinicId', $clinic->id);

        $idempotencyKey = (string) Str::uuid();
        $payload = $this->paymentPayload($clinic->id, $patient->id);

        IdempotencyKey::create([
            'key' => $idempotencyKey,
            'user_id' => $user->id,
            'clinic_id' => $clinic->id,
            'endpoint' => 'api/v1/payments',
            'method' => 'POST',
            'request_hash' => hash('sha256', json_encode($payload)),
            'response_status' => 201,
            'response_body' => ['data' => ['id' => 999]],
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/payments', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }
}
