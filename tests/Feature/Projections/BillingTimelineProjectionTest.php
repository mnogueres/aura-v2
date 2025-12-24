<?php

namespace Tests\Feature\Projections;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoiceIssued;
use App\Events\Billing\InvoicePaid;
use App\Events\Billing\PaymentRecorded;
use App\Models\BillingTimeline;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Repositories\BillingTimelineRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTimelineProjectionTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private Patient $patient;
    private BillingTimelineRepository $repository;

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
        $this->repository = app(BillingTimelineRepository::class);

        // Set current clinic context
        app()->instance('currentClinicId', $this->clinic->id);
    }

    public function test_invoice_created_event_creates_timeline_entry(): void
    {
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
            request_id: 'req-123',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert timeline entry exists
        $timeline = $this->repository->getForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(1, $timeline);
        $this->assertEquals('billing.invoice.created', $timeline->first()->event_name);
        $this->assertEquals($invoice->id, $timeline->first()->reference_id);
        $this->assertNull($timeline->first()->amount);
    }

    public function test_payment_recorded_event_creates_timeline_entry_with_amount(): void
    {
        $payment = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 100.50,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Emit event
        event(new PaymentRecorded(
            payment_id: $payment->id,
            invoice_id: null,
            amount: 100.50,
            request_id: 'req-456',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert timeline entry exists
        $timeline = $this->repository->getForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(1, $timeline);
        $this->assertEquals('billing.payment.recorded', $timeline->first()->event_name);
        $this->assertEquals(100.50, (float) $timeline->first()->amount);
        $this->assertEquals($payment->id, $timeline->first()->reference_id);
    }

    public function test_timeline_entries_are_ordered_chronologically(): void
    {
        $invoice = Invoice::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-002-25',
            'invoice_date' => now(),
            'status' => 'draft',
        ]);

        $payment = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Emit events in random order with delays
        sleep(1);
        event(new PaymentRecorded(
            payment_id: $payment->id,
            invoice_id: null,
            amount: 50.00,
            request_id: 'req-002',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        sleep(1);
        event(new InvoiceCreated(
            invoice_id: $invoice->id,
            status: 'draft',
            request_id: 'req-001',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert timeline is ordered by occurred_at
        $timeline = $this->repository->getForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(2, $timeline);
        $this->assertEquals('billing.payment.recorded', $timeline[0]->event_name);
        $this->assertEquals('billing.invoice.created', $timeline[1]->event_name);
    }

    public function test_duplicate_events_are_not_created_on_replay(): void
    {
        $payment = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 75.00,
            'payment_method' => 'card',
            'payment_date' => now(),
        ]);

        $requestId = 'req-duplicate-test';

        // Emit same event twice (simulating replay)
        event(new PaymentRecorded(
            payment_id: $payment->id,
            invoice_id: null,
            amount: 75.00,
            request_id: $requestId,
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new PaymentRecorded(
            payment_id: $payment->id,
            invoice_id: null,
            amount: 75.00,
            request_id: $requestId,
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert only one timeline entry exists
        $timeline = $this->repository->getForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(1, $timeline, 'Timeline should not contain duplicate entries for the same event');
    }

    public function test_projection_does_not_write_to_domain_tables(): void
    {
        $invoice = Invoice::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-003-25',
            'invoice_date' => now(),
            'status' => 'draft',
        ]);

        $initialInvoiceCount = Invoice::count();
        $initialPaymentCount = Payment::count();

        // Emit event
        event(new InvoiceCreated(
            invoice_id: $invoice->id,
            status: 'draft',
            request_id: 'req-domain-test',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert domain tables were not modified
        $this->assertEquals($initialInvoiceCount, Invoice::count());
        $this->assertEquals($initialPaymentCount, Payment::count());

        // But timeline was updated
        $this->assertEquals(1, BillingTimeline::count());
    }

    public function test_timeline_is_scoped_to_clinic(): void
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

        $invoice1 = Invoice::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-004-25',
            'invoice_date' => now(),
            'status' => 'draft',
        ]);

        $invoice2 = Invoice::create([
            'clinic_id' => $otherClinic->id,
            'patient_id' => $otherPatient->id,
            'invoice_number' => 'INV-005-25',
            'invoice_date' => now(),
            'status' => 'draft',
        ]);

        // Emit events for both clinics
        event(new InvoiceCreated(
            invoice_id: $invoice1->id,
            status: 'draft',
            request_id: 'req-clinic-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new InvoiceCreated(
            invoice_id: $invoice2->id,
            status: 'draft',
            request_id: 'req-clinic-2',
            user_id: 1,
            clinic_id: $otherClinic->id
        ));

        // Assert timelines are separated
        $timeline1 = $this->repository->getForPatient($this->clinic->id, $this->patient->id);
        $timeline2 = $this->repository->getForPatient($otherClinic->id, $otherPatient->id);

        $this->assertCount(1, $timeline1);
        $this->assertCount(1, $timeline2);
        $this->assertNotEquals($timeline1->first()->id, $timeline2->first()->id);
    }

    public function test_reference_id_is_persisted_correctly(): void
    {
        $invoice = Invoice::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-006-25',
            'invoice_date' => now(),
            'status' => 'draft',
        ]);

        // Emit event
        event(new InvoiceCreated(
            invoice_id: $invoice->id,
            status: 'draft',
            request_id: 'req-reference-test',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        $timeline = $this->repository->getForPatient($this->clinic->id, $this->patient->id);

        $this->assertEquals($invoice->id, $timeline->first()->reference_id);
    }

    public function test_multiple_event_types_create_timeline_entries(): void
    {
        $invoice = Invoice::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-007-25',
            'invoice_date' => now(),
            'status' => 'draft',
        ]);

        $payment = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 120.00,
            'payment_method' => 'transfer',
            'payment_date' => now(),
        ]);

        // Emit multiple events
        event(new InvoiceCreated(
            invoice_id: $invoice->id,
            status: 'draft',
            request_id: 'req-multi-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new InvoiceIssued(
            invoice_id: $invoice->id,
            status: 'issued',
            request_id: 'req-multi-2',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new PaymentRecorded(
            payment_id: $payment->id,
            invoice_id: $invoice->id,
            amount: 120.00,
            request_id: 'req-multi-3',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        event(new InvoicePaid(
            invoice_id: $invoice->id,
            status: 'paid',
            request_id: 'req-multi-4',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert all events are in timeline
        $timeline = $this->repository->getForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(4, $timeline);
        $eventNames = $timeline->pluck('event_name')->toArray();
        $this->assertContains('billing.invoice.created', $eventNames);
        $this->assertContains('billing.invoice.issued', $eventNames);
        $this->assertContains('billing.payment.recorded', $eventNames);
        $this->assertContains('billing.invoice.paid', $eventNames);
    }
}
