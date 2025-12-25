<?php

namespace App\Events\Billing;

use App\Events\DomainEvent;

/**
 * Event: billing.invoice.issued
 *
 * Emitted when: Invoice status transitions to "issued"
 *
 * Payload:
 * {
 *   "invoice_id": 456,
 *   "status": "issued"
 * }
 */
class InvoiceIssued extends DomainEvent
{
    public function __construct(
        int $invoice_id,
        string $status,
        ?string $request_id = null,
        ?int $user_id = null,
        ?int $clinic_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'invoice_id' => $invoice_id,
                'status' => $status,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'billing.invoice.issued';
    }
}
