<?php

namespace App\Events\Billing;

use App\Events\DomainEvent;

/**
 * Event: billing.payment.recorded
 *
 * Emitted when: POST /payments → 201
 *
 * Payload:
 * {
 *   "payment_id": 789,
 *   "invoice_id": 456,
 *   "amount": 50.00
 * }
 */
class PaymentRecorded extends DomainEvent
{
    public function __construct(
        int $payment_id,
        ?int $invoice_id,
        float $amount,
        ?string $request_id = null,
        ?int $user_id = null,
        ?int $clinic_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'payment_id' => $payment_id,
                'invoice_id' => $invoice_id,
                'amount' => $amount,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'billing.payment.recorded';
    }
}
