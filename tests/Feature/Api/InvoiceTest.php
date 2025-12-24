<?php

namespace Tests\Feature\Api;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    /** @test */
    public function it_creates_an_invoice_and_returns_calculated_totals()
    {
        $clinic = Clinic::create(['name' => 'Clinic A']);

        $user = User::factory()->create([
            'clinic_id' => $clinic->id,
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $patient = Patient::create([
            'clinic_id'  => $clinic->id,
            'first_name' => 'Ana',
            'last_name'  => 'Test',
        ]);

        $invoice = Invoice::create([
            'clinic_id'      => $clinic->id,
            'patient_id'     => $patient->id,
            'invoice_number' => 'INV-001',
            'invoice_date'   => now(),
            'status'         => 'issued',
        ]);

        InvoiceItem::create([
            'clinic_id'    => $clinic->id,
            'invoice_id'   => $invoice->id,
            'description'  => 'Treatment A',
            'quantity'     => 1,
            'unit_price'   => 100,
            'tax_percent'  => 21,
        ]);

        $response = $this->getJson('/api/v1/invoices');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.subtotal', 100)
            ->assertJsonPath('data.0.tax_total', 21)
            ->assertJsonPath('data.0.total', 121)
            ->assertJsonPath('data.0.balance_due', 121);
    }

    /** @test */
    public function it_returns_invoice_envelope_with_pagination_meta()
    {
        $clinic = Clinic::create(['name' => 'Clinic A']);

        $user = User::factory()->create([
            'clinic_id' => $clinic->id,
        ]);

        $this->actingAs($user);
        app()->instance('currentClinicId', $clinic->id);

        $patient = Patient::create([
            'clinic_id'  => $clinic->id,
            'first_name' => 'Ana',
            'last_name'  => 'Test',
        ]);

        $invoice = Invoice::create([
            'clinic_id'      => $clinic->id,
            'patient_id'     => $patient->id,
            'invoice_number' => 'INV-001',
            'invoice_date'   => now(),
            'status'         => 'issued',
        ]);

        InvoiceItem::create([
            'clinic_id'    => $clinic->id,
            'invoice_id'   => $invoice->id,
            'description'  => 'Treatment A',
            'quantity'     => 1,
            'unit_price'   => 100,
            'tax_percent'  => 21,
        ]);

        $response = $this->getJson('/api/v1/invoices');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                    ],
                ],
            ])
            ->assertJsonPath('meta.pagination.per_page', 8)
            ->assertJsonPath('data.0.subtotal', 100)
            ->assertJsonPath('data.0.tax_total', 21)
            ->assertJsonPath('data.0.total', 121)
            ->assertJsonPath('data.0.paid_amount', 0)
            ->assertJsonPath('data.0.balance_due', 121);
    }

    /** @test */
    public function it_returns_standard_error_on_forbidden()
    {
        $clinic = Clinic::create(['name' => 'Clinic']);

        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->actingAs($user);

        // NO set currentClinicId context - policy should fail
        $patient = Patient::create([
            'clinic_id'  => $clinic->id,
            'first_name' => 'Test',
            'last_name'  => 'Patient',
        ]);

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/invoices', [
                'clinic_id'      => $clinic->id,
                'patient_id'     => $patient->id,
                'invoice_number' => 'INV-001',
                'invoice_date'   => now()->toDateString(),
                'status'         => 'draft',
            ]);

        $response
            ->assertStatus(403)
            ->assertJsonStructure([
                'data',
                'error' => ['code', 'message'],
                'meta' => ['request_id'],
            ])
            ->assertJsonPath('error.code', 'forbidden')
            ->assertJsonPath('data', null);
    }
}
