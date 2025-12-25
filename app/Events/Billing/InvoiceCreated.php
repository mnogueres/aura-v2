<?php

namespace App\Events\Billing;

use App\Events\DomainEvent;

/**
 * Event: billing.invoice.created
 *
 * Emitted when: POST /invoices → 201
 *
 * Payload:
 * {
 *   "invoice_id": 456,
 *   "status": "draft"
 * }
 */
class InvoiceCreated extends DomainEvent
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
        return 'billing.invoice.created';
    }
}
