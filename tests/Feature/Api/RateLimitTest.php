<?php

namespace Tests\Feature\Api;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_rate_limits_payment_creation()
    {
        $clinic = Clinic::create(['name' => 'Clinic']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $patient = Patient::create([
            'clinic_id' => $clinic->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
        ]);

        $invoice = Invoice::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-1',
            'invoice_date' => now(),
            'status' => 'issued',
        ]);

        InvoiceItem::create([
            'clinic_id' => $clinic->id,
            'invoice_id' => $invoice->id,
            'description' => 'Treatment',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_percent' => 21,
        ]);

        // Make 10 successful payment requests (within limit)
        for ($i = 0; $i < 10; $i++) {
            $this->withHeader('Idempotency-Key', (string) Str::uuid())
                ->postJson('/api/v1/payments', [
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'invoice_id' => $invoice->id,
                    'amount' => 5,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => 'cash',
                ])->assertStatus(201);
        }

        // 11th request should be rate limited
        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/payments', [
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'invoice_id' => $invoice->id,
                'amount' => 5,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'cash',
            ]);

        $response
            ->assertStatus(429)
            ->assertJsonStructure([
                'data',
                'error' => ['code', 'message', 'details'],
                'meta' => ['request_id'],
            ])
            ->assertJsonPath('error.code', 'rate_limited')
            ->assertJsonPath('data', null);
    }
}
