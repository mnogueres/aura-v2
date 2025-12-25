<?php

namespace App\Repositories;

use App\Contracts\OutboxRepositoryInterface;
use App\Events\DomainEvent;
use App\Models\EventOutbox;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * OutboxRepository - Concrete implementation for event outbox persistence.
 *
 * Handles the technical details of storing domain events
 * in the outbox table for durability and future processing.
 */
class OutboxRepository implements OutboxRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function store(DomainEvent $event): EventOutbox
    {
        return EventOutbox::create([
            'clinic_id' => $event->clinic_id,
            'event_name' => $event->event,
            'payload' => $event->payload,
            'occurred_at' => Carbon::parse($event->occurred_at),
            'recorded_at' => Carbon::now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPending(int $limit = 100): Collection
    {
        return EventOutbox::pending()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getFailed(int $limit = 100): Collection
    {
        return EventOutbox::failed()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
