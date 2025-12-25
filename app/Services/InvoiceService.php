<?php

namespace App\Services;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoiceIssued;
use App\Events\Billing\InvoicePaid;
use App\Models\Invoice;

/**
 * InvoiceService - Business logic for Invoice operations.
 *
 * Emits:
 * - billing.invoice.created
 * - billing.invoice.issued
 * - billing.invoice.paid
 */
class InvoiceService
{
    public function __construct(
        private readonly EventService $eventService
    ) {
    }

    /**
     * Create a new invoice and emit InvoiceCreated event.
     *
     * @param array $data
     * @return Invoice
     */
    public function create(array $data): Invoice
    {
        $invoice = Invoice::create($data);

        $this->eventService->emit(
            new InvoiceCreated(
                invoice_id: $invoice->id,
                status: $invoice->status
            )
        );

        // If created directly as "issued", emit InvoiceIssued
        if ($invoice->status === 'issued') {
            $this->eventService->emit(
                new InvoiceIssued(
                    invoice_id: $invoice->id,
                    status: $invoice->status
                )
            );
        }

        return $invoice;
    }

    /**
     * Check if invoice should transition to "paid" status and emit event.
     * Called after payment is applied.
     *
     * @param Invoice $invoice
     * @return void
     */
    public function checkPaidStatus(Invoice $invoice): void
    {
        // Reload invoice to get fresh balance
        $invoice->refresh();

        if ($invoice->balanceDue() <= 0 && $invoice->status !== 'paid') {
            $invoice->update(['status' => 'paid']);

            $this->eventService->emit(
                new InvoicePaid(
                    invoice_id: $invoice->id,
                    status: 'paid'
                )
            );
        }
    }
}
