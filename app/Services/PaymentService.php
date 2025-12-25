<?php

namespace App\Services;

use App\Events\Billing\PaymentApplied;
use App\Events\Billing\PaymentRecorded;
use App\Events\Billing\PaymentUnlinked;
use App\Models\Invoice;
use App\Models\Payment;

/**
 * PaymentService - Business logic for Payment operations.
 *
 * Emits:
 * - billing.payment.recorded
 * - billing.payment.applied
 * - billing.payment.unlinked
 */
class PaymentService
{
    public function __construct(
        private readonly EventService $eventService,
        private readonly InvoiceService $invoiceService
    ) {
    }

    /**
     * Create a new payment and emit appropriate events.
     *
     * @param array $data
     * @return Payment
     */
    public function create(array $data): Payment
    {
        $payment = Payment::create($data);

        // Always emit PaymentRecorded
        $this->eventService->emit(
            new PaymentRecorded(
                payment_id: $payment->id,
                invoice_id: $payment->invoice_id,
                amount: $payment->amount
            )
        );

        // Emit PaymentApplied or PaymentUnlinked based on invoice_id
        if ($payment->invoice_id !== null) {
            $this->eventService->emit(
                new PaymentApplied(
                    payment_id: $payment->id,
                    invoice_id: $payment->invoice_id,
                    amount: $payment->amount
                )
            );

            // Check if invoice should transition to "paid" status
            $invoice = Invoice::find($payment->invoice_id);
            if ($invoice) {
                $this->invoiceService->checkPaidStatus($invoice);
            }
        } else {
            $this->eventService->emit(
                new PaymentUnlinked(
                    payment_id: $payment->id,
                    amount: $payment->amount
                )
            );
        }

        return $payment;
    }
}
