<?php

namespace App\Events\Billing;

use App\Events\DomainEvent;

/**
 * Event: billing.invoice.paid
 *
 * Emitted when: Invoice balance_due reaches 0
 *
 * Payload:
 * {
 *   "invoice_id": 456,
 *   "status": "paid"
 * }
 */
class InvoicePaid extends DomainEvent
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
        return 'billing.invoice.paid';
    }
}
