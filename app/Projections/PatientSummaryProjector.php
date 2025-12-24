<?php

namespace App\Projections;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoicePaid;
use App\Events\Billing\PaymentApplied;
use App\Events\Billing\PaymentRecorded;
use App\Events\Billing\PaymentUnlinked;
use App\Events\CRM\PatientCreated;
use App\Models\PatientSummary;
use Illuminate\Support\Facades\DB;

class PatientSummaryProjector
{
    public function handlePatientCreated(PatientCreated $event): void
    {
        $sourceEventId = $this->generateSourceEventId($event);

        // Check if already projected (idempotency)
        $exists = DB::table('patient_summary')
            ->where('clinic_id', $event->clinic_id)
            ->where('patient_id', $event->payload['patient_id'])
            ->exists();

        if ($exists) {
            return;
        }

        PatientSummary::create([
            'clinic_id' => $event->clinic_id,
            'patient_id' => $event->payload['patient_id'],
            'created_at_occurred' => $event->occurred_at,
            'last_activity_at' => $event->occurred_at,
            'invoices_count' => 0,
            'payments_count' => 0,
            'total_invoiced_amount' => 0,
            'total_paid_amount' => 0,
            'projected_at' => now(),
        ]);
    }

    public function handleInvoiceCreated(InvoiceCreated $event): void
    {
        $patientId = $this->getPatientIdFromInvoice($event->payload['invoice_id']);
        if (!$patientId) {
            return;
        }

        $this->updateSummary(
            clinicId: $event->clinic_id,
            patientId: $patientId,
            occurredAt: $event->occurred_at,
            updates: function ($summary) use ($event) {
                $summary->increment('invoices_count');
                $this->updateLastActivityAt($summary, $event->occurred_at);
            }
        );
    }

    public function handleInvoicePaid(InvoicePaid $event): void
    {
        $patientId = $this->getPatientIdFromInvoice($event->payload['invoice_id']);
        if (!$patientId) {
            return;
        }

        // Get invoice total from payload or database
        $invoiceTotal = $this->getInvoiceTotal($event->payload['invoice_id']);

        $this->updateSummary(
            clinicId: $event->clinic_id,
            patientId: $patientId,
            occurredAt: $event->occurred_at,
            updates: function ($summary) use ($event, $invoiceTotal) {
                $summary->increment('total_invoiced_amount', $invoiceTotal);
                $this->updateLastActivityAt($summary, $event->occurred_at);
            }
        );
    }

    public function handlePaymentRecorded(PaymentRecorded $event): void
    {
        $patientId = $this->getPatientIdFromPayment($event->payload['payment_id']);
        if (!$patientId) {
            return;
        }

        $this->updateSummary(
            clinicId: $event->clinic_id,
            patientId: $patientId,
            occurredAt: $event->occurred_at,
            updates: function ($summary) use ($event) {
                $summary->increment('payments_count');
                $summary->increment('total_paid_amount', $event->payload['amount']);
                $this->updateLastActivityAt($summary, $event->occurred_at);
            }
        );
    }

    public function handlePaymentApplied(PaymentApplied $event): void
    {
        $patientId = $this->getPatientIdFromPayment($event->payload['payment_id']);
        if (!$patientId) {
            return;
        }

        $this->updateSummary(
            clinicId: $event->clinic_id,
            patientId: $patientId,
            occurredAt: $event->occurred_at,
            updates: function ($summary) use ($event) {
                $this->updateLastActivityAt($summary, $event->occurred_at);
            }
        );
    }

    public function handlePaymentUnlinked(PaymentUnlinked $event): void
    {
        $patientId = $this->getPatientIdFromPayment($event->payload['payment_id']);
        if (!$patientId) {
            return;
        }

        $this->updateSummary(
            clinicId: $event->clinic_id,
            patientId: $patientId,
            occurredAt: $event->occurred_at,
            updates: function ($summary) use ($event) {
                $this->updateLastActivityAt($summary, $event->occurred_at);
            }
        );
    }

    private function updateSummary(int $clinicId, int $patientId, string $occurredAt, callable $updates): void
    {
        $summary = PatientSummary::where('clinic_id', $clinicId)
            ->where('patient_id', $patientId)
            ->first();

        if (!$summary) {
            return;
        }

        $updates($summary);
        $summary->update(['projected_at' => now()]);
    }

    private function updateLastActivityAt(PatientSummary $summary, string $occurredAt): void
    {
        $newActivityAt = \Carbon\Carbon::parse($occurredAt);

        if (!$summary->last_activity_at || $newActivityAt->greaterThan($summary->last_activity_at)) {
            $summary->last_activity_at = $newActivityAt;
            $summary->save();
        }
    }

    private function generateSourceEventId(object $event): string
    {
        return hash('sha256', $event->request_id . $event->event . $event->occurred_at);
    }

    private function getPatientIdFromInvoice(int $invoiceId): ?int
    {
        return \App\Models\Invoice::find($invoiceId)?->patient_id;
    }

    private function getPatientIdFromPayment(int $paymentId): ?int
    {
        return \App\Models\Payment::find($paymentId)?->patient_id;
    }

    private function getInvoiceTotal(int $invoiceId): float
    {
        $invoice = \App\Models\Invoice::with('items')->find($invoiceId);
        if (!$invoice) {
            return 0.0;
        }

        // Calculate total using Invoice model method
        return $invoice->total();
    }
}
