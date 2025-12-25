<?php

namespace App\Projections;

use App\Events\Platform\IdempotencyConflict;
use App\Events\Platform\IdempotencyReplayed;
use App\Events\Platform\RateLimited;
use App\Models\AuditTrail;

class AuditTrailProjector
{
    private const EVENT_MAPPING = [
        'platform.rate_limited' => [
            'category' => 'security',
            'severity' => 'warning',
        ],
        'platform.idempotency.replayed' => [
            'category' => 'platform',
            'severity' => 'info',
        ],
        'platform.idempotency.conflict' => [
            'category' => 'security',
            'severity' => 'error',
        ],
    ];

    public function handleRateLimited(RateLimited $event): void
    {
        $this->project(
            eventName: 'platform.rate_limited',
            eventData: $event
        );
    }

    public function handleIdempotencyReplayed(IdempotencyReplayed $event): void
    {
        $this->project(
            eventName: 'platform.idempotency.replayed',
            eventData: $event
        );
    }

    public function handleIdempotencyConflict(IdempotencyConflict $event): void
    {
        $this->project(
            eventName: 'platform.idempotency.conflict',
            eventData: $event
        );
    }

    private function project(string $eventName, object $eventData): void
    {
        $sourceEventId = $this->generateSourceEventId($eventData);

        $mapping = self::EVENT_MAPPING[$eventName] ?? [
            'category' => 'platform',
            'severity' => 'info',
        ];

        AuditTrail::firstOrCreate(
            ['source_event_id' => $sourceEventId],
            [
                'clinic_id' => $eventData->clinic_id,
                'event_name' => $eventName,
                'category' => $mapping['category'],
                'severity' => $mapping['severity'],
                'actor_type' => $eventData->user_id ? 'user' : 'system',
                'actor_id' => $eventData->user_id,
                'context' => $eventData->payload,
                'occurred_at' => $eventData->occurred_at,
                'projected_at' => now(),
            ]
        );
    }

    private function generateSourceEventId(object $event): string
    {
        return hash('sha256', $event->request_id . $event->event . $event->occurred_at);
    }
}
