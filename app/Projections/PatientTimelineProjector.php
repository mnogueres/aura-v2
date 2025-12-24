<?php

namespace App\Projections;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoiceIssued;
use App\Events\Billing\InvoicePaid;
use App\Events\Billing\PaymentApplied;
use App\Events\Billing\PaymentRecorded;
use App\Events\Billing\PaymentUnlinked;
use App\Events\CRM\PatientCreated;
use App\Models\PatientTimeline;
use Illuminate\Support\Str;

class PatientTimelineProjector
{
    public function handlePatientCreated(PatientCreated $event): void
    {
        $this->project(
            eventName: 'crm.patient.created',
            eventData: $event,
            patientId: $event->payload['patient_id']
        );
    }

    public function handleInvoiceCreated(InvoiceCreated $event): void
    {
        $this->project(
            eventName: 'billing.invoice.created',
            eventData: $event,
            patientId: $this->getPatientIdFromInvoice($event->payload['invoice_id'])
        );
    }

    public function handleInvoiceIssued(InvoiceIssued $event): void
    {
        $this->project(
            eventName: 'billing.invoice.issued',
            eventData: $event,
            patientId: $this->getPatientIdFromInvoice($event->payload['invoice_id'])
        );
    }

    public function handleInvoicePaid(InvoicePaid $event): void
    {
        $this->project(
            eventName: 'billing.invoice.paid',
            eventData: $event,
            patientId: $this->getPatientIdFromInvoice($event->payload['invoice_id'])
        );
    }

    public function handlePaymentRecorded(PaymentRecorded $event): void
    {
        $this->project(
            eventName: 'billing.payment.recorded',
            eventData: $event,
            patientId: $this->getPatientIdFromPayment($event->payload['payment_id'])
        );
    }

    public function handlePaymentApplied(PaymentApplied $event): void
    {
        $this->project(
            eventName: 'billing.payment.applied',
            eventData: $event,
            patientId: $this->getPatientIdFromPayment($event->payload['payment_id'])
        );
    }

    public function handlePaymentUnlinked(PaymentUnlinked $event): void
    {
        $this->project(
            eventName: 'billing.payment.unlinked',
            eventData: $event,
            patientId: $this->getPatientIdFromPayment($event->payload['payment_id'])
        );
    }

    private function project(string $eventName, object $eventData, ?int $patientId): void
    {
        if (!$patientId) {
            return;
        }

        // Use request_id + event_name as unique identifier
        // This ensures one event per request = one timeline entry
        $sourceEventId = hash('sha256', $eventData->request_id . $eventName . $eventData->occurred_at);

        PatientTimeline::firstOrCreate(
            ['source_event_id' => $sourceEventId],
            [
                'clinic_id' => $eventData->clinic_id,
                'patient_id' => $patientId,
                'event_name' => $eventName,
                'event_payload' => $eventData->payload,
                'occurred_at' => $eventData->occurred_at,
                'projected_at' => now(),
            ]
        );
    }

    private function getPatientIdFromInvoice(int $invoiceId): ?int
    {
        return \App\Models\Invoice::find($invoiceId)?->patient_id;
    }

    private function getPatientIdFromPayment(int $paymentId): ?int
    {
        return \App\Models\Payment::find($paymentId)?->patient_id;
    }
}
