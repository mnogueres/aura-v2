<?php

namespace Tests\Feature\Projections;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoicePaid;
use App\Events\Billing\PaymentRecorded;
use App\Events\CRM\PatientCreated;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\PatientSummary;
use App\Models\Payment;
use App\Repositories\PatientSummaryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSummaryProjectionTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private Patient $patient;
    private PatientSummaryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::create(['name' => 'Test Clinic']);
        $this->patient = Patient::create([
            'clinic_id' => $this->clinic->id,
            'dni' => '12345678A',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
        ]);
        $this->repository = app(PatientSummaryRepository::class);

        // Set current clinic context
        app()->instance('currentClinicId', $this->clinic->id);
    }

    public function test_summary_is_created_on_patient_created_event(): void
    {
        // Emit event
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-123',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert summary exists
        $summary = $this->repository->getByPatient($this->clinic->id, $this->patient->id);

        $this->assertNotNull($summary);
        $this->assertEquals($this->clinic->id, $summary->clinic_id);
        $this->assertEquals($this->patient->id, $summary->patient_id);
        $this->assertEquals(0, $summary->invoices_count);
        $this->assertEquals(0, $summary->payments_count);
        $this->assertEquals(0, $summary->total_invoiced_amount);
        $this->assertEquals(0, $summary->total_paid_amount);
        $this->assertNotNull($summary->created_at_occurred);
        $this->assertNotNull($summary->last_activity_at);
    }

    public function test_invoices_count_increments_on_invoice_created(): void
    {
        // Create summary first
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-001',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        $invoice = Invoice::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-001-25',
            'invoice_date' => now(),
            'status' => 'draft',
        ]);

        // Emit event
        event(new InvoiceCreated(
            invoice_id: $invoice->id,
            status: 'draft',
            request_id: 'req-002',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert count incremented
        $summary = $this->repository->getByPatient($this->clinic->id, $this->patient->id);

        $this->assertEquals(1, $summary->invoices_count);
    }

    public function test_payments_count_increments_on_payment_recorded(): void
    {
        // Create summary first
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-001',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        $payment = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Emit event
        event(new PaymentRecorded(
            payment_id: $payment->id,
            invoice_id: null,
            amount: 50.00,
            request_id: 'req-002',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert count incremented
        $summary = $this->repository->getByPatient($this->clinic->id, $this->patient->id);

        $this->assertEquals(1, $summary->payments_count);
    }

    public function test_total_paid_amount_increments_correctly(): void
    {
        // Create summary first
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-001',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        $payment1 = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        $payment2 = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 75.50,
            'payment_method' => 'card',
            'payment_date' => now(),
        ]);

        // Emit events
        event(new PaymentRecorded(
            payment_id: $payment1->id,
            invoice_id: null,
            amount: 50.00,
            request_id: 'req-002',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new PaymentRecorded(
            payment_id: $payment2->id,
            invoice_id: null,
            amount: 75.50,
            request_id: 'req-003',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert total is correct
        $summary = $this->repository->getByPatient($this->clinic->id, $this->patient->id);

        $this->assertEquals(125.50, $summary->total_paid_amount);
        $this->assertEquals(2, $summary->payments_count);
    }

    public function test_total_invoiced_amount_updates_on_invoice_paid(): void
    {
        // Create summary first
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-001',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        $invoice = Invoice::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-001-25',
            'invoice_date' => now(),
            'status' => 'issued',
        ]);

        // Create invoice items
        InvoiceItem::create([
            'clinic_id' => $this->clinic->id,
            'invoice_id' => $invoice->id,
            'description' => 'Service 1',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        // Emit event
        event(new InvoicePaid(
            invoice_id: $invoice->id,
            status: 'paid',
            request_id: 'req-002',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert total invoiced amount updated
        $summary = $this->repository->getByPatient($this->clinic->id, $this->patient->id);

        $this->assertEquals(100.00, $summary->total_invoiced_amount);
    }

    public function test_last_activity_at_updates_on_new_events(): void
    {
        // Create summary
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-001',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        $summary = $this->repository->getByPatient($this->clinic->id, $this->patient->id);
        $initialActivityAt = $summary->last_activity_at;

        sleep(1);

        $payment = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Emit new event
        event(new PaymentRecorded(
            payment_id: $payment->id,
            invoice_id: null,
            amount: 50.00,
            request_id: 'req-002',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        $summary->refresh();

        $this->assertGreaterThan($initialActivityAt, $summary->last_activity_at);
    }

    public function test_summary_does_not_duplicate_on_replay(): void
    {
        $requestId = 'req-duplicate-test';

        // Emit same event twice
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: $requestId,
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: $requestId,
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert only one summary exists
        $count = PatientSummary::where('clinic_id', $this->clinic->id)
            ->where('patient_id', $this->patient->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_projection_does_not_write_to_domain_tables(): void
    {
        $initialPatientCount = Patient::count();
        $initialInvoiceCount = Invoice::count();
        $initialPaymentCount = Payment::count();

        // Emit event
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-domain-test',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert domain tables were not modified
        $this->assertEquals($initialPatientCount, Patient::count());
        $this->assertEquals($initialInvoiceCount, Invoice::count());
        $this->assertEquals($initialPaymentCount, Payment::count());

        // But summary was created
        $this->assertEquals(1, PatientSummary::count());
    }

    public function test_summary_is_scoped_to_clinic(): void
    {
        $otherClinic = Clinic::create(['name' => 'Other Clinic']);
        $otherPatient = Patient::create([
            'clinic_id' => $otherClinic->id,
            'dni' => '87654321B',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '987654321',
        ]);

        // Emit events for both patients
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-clinic-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new PatientCreated(
            patient_id: $otherPatient->id,
            request_id: 'req-clinic-2',
            user_id: 1,
            clinic_id: $otherClinic->id
        ));

        // Assert summaries are separated
        $summary1 = $this->repository->getByPatient($this->clinic->id, $this->patient->id);
        $summary2 = $this->repository->getByPatient($otherClinic->id, $otherPatient->id);

        $this->assertNotNull($summary1);
        $this->assertNotNull($summary2);
        $this->assertNotEquals($summary1->id, $summary2->id);
        $this->assertEquals($this->clinic->id, $summary1->clinic_id);
        $this->assertEquals($otherClinic->id, $summary2->clinic_id);
    }
}
