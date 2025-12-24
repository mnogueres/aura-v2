<?php

namespace Tests\Feature\Api;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    /** @test */
    public function it_registers_partial_payments_and_updates_invoice_balance()
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
            'invoice_number' => 'INV-002',
            'invoice_date'   => now(),
            'status'         => 'issued',
        ]);

        InvoiceItem::create([
            'clinic_id'   => $clinic->id,
            'invoice_id'  => $invoice->id,
            'description' => 'Treatment A',
            'quantity'    => 1,
            'unit_price'  => 100,
            'tax_percent' => 21,
        ]);

        // Pago parcial
        Payment::create([
            'clinic_id'     => $clinic->id,
            'patient_id'    => $patient->id,
            'invoice_id'    => $invoice->id,
            'amount'        => 50,
            'payment_date'  => now(),
            'payment_method'=> 'cash',
        ]);

        $response = $this->getJson('/api/v1/invoices');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.total', 121)
            ->assertJsonPath('data.0.paid_amount', 50)
            ->assertJsonPath('data.0.balance_due', 71);
    }

    /** @test */
    public function it_returns_payment_envelope_with_pagination_meta()
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

        Payment::create([
            'clinic_id'      => $clinic->id,
            'patient_id'     => $patient->id,
            'invoice_id'     => $invoice->id,
            'amount'         => 50,
            'payment_date'   => now(),
            'payment_method' => 'cash',
        ]);

        Payment::create([
            'clinic_id'      => $clinic->id,
            'patient_id'     => $patient->id,
            'invoice_id'     => null,
            'amount'         => 25,
            'payment_date'   => now(),
            'payment_method' => 'card',
        ]);

        $response = $this->getJson('/api/v1/payments');

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
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.amount', 25)
            ->assertJsonPath('data.1.payment_date', now()->toDateString())
            ->assertJsonPath('data.1.patient.id', $patient->id)
            ->assertJsonPath('data.1.invoice', null)
            ->assertJsonPath('data.0.amount', 50)
            ->assertJsonPath('data.0.invoice.id', $invoice->id);
    }
}
