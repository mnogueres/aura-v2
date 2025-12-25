<?php

namespace App\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base class for all domain events.
 *
 * Every domain event shares this common envelope structure:
 * {
 *   "event": "billing.payment.recorded",
 *   "occurred_at": "2025-12-24T12:34:56Z",
 *   "request_id": "uuid",
 *   "user_id": 1,
 *   "clinic_id": 2,
 *   "payload": { ... }
 * }
 */
abstract class DomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly string $event;
    public readonly string $occurred_at;
    public readonly ?string $request_id;
    public readonly ?int $user_id;
    public readonly ?int $clinic_id;
    public readonly array $payload;

    public function __construct(
        string $event,
        array $payload,
        ?string $request_id = null,
        ?int $user_id = null,
        ?int $clinic_id = null
    ) {
        $this->event = $event;
        $this->occurred_at = Carbon::now()->toIso8601String();
        $this->request_id = $request_id ?? request()->header('X-Request-Id');
        $this->user_id = $user_id ?? auth()->id();
        $this->clinic_id = $clinic_id ?? app('currentClinicId');
        $this->payload = $payload;
    }

    /**
     * Get the event envelope as an array.
     */
    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'occurred_at' => $this->occurred_at,
            'request_id' => $this->request_id,
            'user_id' => $this->user_id,
            'clinic_id' => $this->clinic_id,
            'payload' => $this->payload,
        ];
    }

    /**
     * Get the event name (for taxonomy).
     */
    abstract public static function eventName(): string;
}
