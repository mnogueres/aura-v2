<?php

namespace App\Events\Platform;

use App\Events\DomainEvent;

/**
 * Event: platform.rate_limited
 *
 * Emitted when: 429 Too Many Requests response is returned
 *
 * Payload:
 * {
 *   "endpoint": "/api/v1/payments",
 *   "method": "POST"
 * }
 */
class RateLimited extends DomainEvent
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
        return 'platform.rate_limited';
    }
}
