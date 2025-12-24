<?php

namespace Tests\Feature\Projections;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\PaymentRecorded;
use App\Events\CRM\PatientCreated;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientTimeline;
use App\Models\Payment;
use App\Repositories\PatientTimelineRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PatientTimelineProjectionTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;
    private Patient $patient;
    private PatientTimelineRepository $repository;

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
        $this->repository = app(PatientTimelineRepository::class);

        // Set current clinic context
        app()->instance('currentClinicId', $this->clinic->id);
    }

    public function test_patient_created_event_appears_in_timeline(): void
    {
        // Emit event
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-123',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert timeline entry exists
        $timeline = $this->repository->getTimelineForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(1, $timeline);
        $this->assertEquals('crm.patient.created', $timeline->first()->event_name);
        $this->assertEquals($this->patient->id, $timeline->first()->event_payload['patient_id']);
    }

    public function test_invoice_created_event_appears_in_timeline(): void
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
            request_id: 'req-456',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert timeline entry exists
        $timeline = $this->repository->getTimelineForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(1, $timeline);
        $this->assertEquals('billing.invoice.created', $timeline->first()->event_name);
        $this->assertEquals($invoice->id, $timeline->first()->event_payload['invoice_id']);
    }

    public function test_payment_recorded_event_appears_in_timeline(): void
    {
        $payment = Payment::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'amount' => 100.00,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Emit event
        event(new PaymentRecorded(
            payment_id: $payment->id,
            invoice_id: null,
            amount: 100.00,
            request_id: 'req-789',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert timeline entry exists
        $timeline = $this->repository->getTimelineForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(1, $timeline);
        $this->assertEquals('billing.payment.recorded', $timeline->first()->event_name);
        $this->assertEquals($payment->id, $timeline->first()->event_payload['payment_id']);
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

        // Emit events in random order
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
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-001',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        sleep(1);
        event(new InvoiceCreated(
            invoice_id: $invoice->id,
            status: 'draft',
            request_id: 'req-003',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert timeline is ordered by occurred_at
        $timeline = $this->repository->getTimelineForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(3, $timeline);
        $this->assertEquals('billing.payment.recorded', $timeline[0]->event_name);
        $this->assertEquals('crm.patient.created', $timeline[1]->event_name);
        $this->assertEquals('billing.invoice.created', $timeline[2]->event_name);
    }

    public function test_duplicate_events_are_not_created_on_replay(): void
    {
        $requestId = 'req-duplicate-test';

        // Emit same event twice (simulating replay)
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

        // Assert only one timeline entry exists
        $timeline = $this->repository->getTimelineForPatient($this->clinic->id, $this->patient->id);

        $this->assertCount(1, $timeline, 'Timeline should not contain duplicate entries for the same event');
    }

    public function test_projection_does_not_write_to_domain_tables(): void
    {
        $initialPatientCount = Patient::count();
        $initialInvoiceCount = Invoice::count();
        $initialPaymentCount = Payment::count();

        // Emit events
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

        // But timeline was updated
        $this->assertEquals(1, PatientTimeline::count());
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

        // Emit event for first patient
        event(new PatientCreated(
            patient_id: $this->patient->id,
            request_id: 'req-clinic-1',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Emit event for other patient
        event(new PatientCreated(
            patient_id: $otherPatient->id,
            request_id: 'req-clinic-2',
            user_id: 1,
            clinic_id: $otherClinic->id
        ));

        // Assert timelines are separated by clinic
        $timeline1 = $this->repository->getTimelineForPatient($this->clinic->id, $this->patient->id);
        $timeline2 = $this->repository->getTimelineForPatient($otherClinic->id, $otherPatient->id);

        $this->assertCount(1, $timeline1);
        $this->assertCount(1, $timeline2);
        $this->assertNotEquals($timeline1->first()->id, $timeline2->first()->id);
    }

    public function test_timeline_handles_missing_patient_gracefully(): void
    {
        $invoice = Invoice::create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'invoice_number' => 'INV-003-25',
            'invoice_date' => now(),
            'status' => 'draft',
        ]);

        // Delete patient
        $this->patient->forceDelete();

        // Emit event (should not crash)
        event(new InvoiceCreated(
            invoice_id: $invoice->id,
            status: 'draft',
            request_id: 'req-missing-patient',
            user_id: 1,
            clinic_id: $this->clinic->id
        ));

        // Assert no timeline entry was created (patient doesn't exist)
        $this->assertEquals(0, PatientTimeline::count());
    }
}
