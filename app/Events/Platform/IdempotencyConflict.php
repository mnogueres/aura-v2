<?php

namespace App\Events\Platform;

use App\Events\DomainEvent;

/**
 * Event: platform.idempotency.conflict
 *
 * Emitted when: Idempotency key is reused with different body (409 Conflict)
 *
 * Payload:
 * {
 *   "endpoint": "/api/v1/payments",
 *   "method": "POST"
 * }
 */
class IdempotencyConflict extends DomainEvent
{
    public function __construct(
        string $endpoint,
        string $method,
        ?string $request_id = null,
        ?int $user_id = null,
        ?int $clinic_id = null
    ) {
        parent::__construct(
            event: self::eventName(),
            payload: [
                'endpoint' => $endpoint,
                'method' => $method,
            ],
            request_id: $request_id,
            user_id: $user_id,
            clinic_id: $clinic_id
        );
    }

    public static function eventName(): string
    {
        return 'platform.idempotency.conflict';
    }
}
