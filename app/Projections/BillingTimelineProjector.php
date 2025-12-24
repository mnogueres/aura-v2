<?php

namespace App\Projections;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoiceIssued;
use App\Events\Billing\InvoicePaid;
use App\Events\Billing\PaymentApplied;
use App\Events\Billing\PaymentRecorded;
use App\Events\Billing\PaymentUnlinked;
use App\Models\BillingTimeline;

class BillingTimelineProjector
{
    public function handleInvoiceCreated(InvoiceCreated $event): void
    {
        $this->project(
            eventName: 'billing.invoice.created',
            eventData: $event,
            patientId: $this->getPatientIdFromInvoice($event->payload['invoice_id']),
            amount: null,
            referenceId: $event->payload['invoice_id']
        );
    }

    public function handleInvoiceIssued(InvoiceIssued $event): void
    {
        $this->project(
            eventName: 'billing.invoice.issued',
            eventData: $event,
            patientId: $this->getPatientIdFromInvoice($event->payload['invoice_id']),
            amount: null,
            referenceId: $event->payload['invoice_id']
        );
    }

    public function handleInvoicePaid(InvoicePaid $event): void
    {
        $this->project(
            eventName: 'billing.invoice.paid',
            eventData: $event,
            patientId: $this->getPatientIdFromInvoice($event->payload['invoice_id']),
            amount: null,
            referenceId: $event->payload['invoice_id']
        );
    }

    public function handlePaymentRecorded(PaymentRecorded $event): void
    {
        $this->project(
            eventName: 'billing.payment.recorded',
            eventData: $event,
            patientId: $this->getPatientIdFromPayment($event->payload['payment_id']),
            amount: $event->payload['amount'],
            referenceId: $event->payload['payment_id']
        );
    }

    public function handlePaymentApplied(PaymentApplied $event): void
    {
        $this->project(
            eventName: 'billing.payment.applied',
            eventData: $event,
            patientId: $this->getPatientIdFromPayment($event->payload['payment_id']),
            amount: $event->payload['amount'],
            referenceId: $event->payload['payment_id']
        );
    }

    public function handlePaymentUnlinked(PaymentUnlinked $event): void
    {
        $this->project(
            eventName: 'billing.payment.unlinked',
            eventData: $event,
            patientId: $this->getPatientIdFromPayment($event->payload['payment_id']),
            amount: $event->payload['amount'],
            referenceId: $event->payload['payment_id']
        );
    }

    private function project(
        string $eventName,
        object $eventData,
        ?int $patientId,
        ?float $amount,
        ?int $referenceId
    ): void {
        if (!$patientId) {
            return;
        }

        $sourceEventId = $this->generateSourceEventId($eventData);

        BillingTimeline::firstOrCreate(
            ['source_event_id' => $sourceEventId],
            [
                'clinic_id' => $eventData->clinic_id,
                'patient_id' => $patientId,
                'event_name' => $eventName,
                'amount' => $amount,
                'currency' => 'EUR',
                'reference_id' => $referenceId,
                'event_payload' => $eventData->payload,
                'occurred_at' => $eventData->occurred_at,
                'projected_at' => now(),
            ]
        );
    }

    private function generateSourceEventId(object $event): string
    {
        return hash('sha256', $event->request_id . $event->event . $event->occurred_at);
    }

    private function getPatientIdFromInvoice(int $invoiceId): ?int
    {
        return \App\Models\Invoice::withoutGlobalScopes()->find($invoiceId)?->patient_id;
    }

    private function getPatientIdFromPayment(int $paymentId): ?int
    {
        return \App\Models\Payment::withoutGlobalScopes()->find($paymentId)?->patient_id;
    }
}
