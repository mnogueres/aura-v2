<?php

namespace App\Contracts;

use App\Events\DomainEvent;
use App\Models\EventOutbox;

/**
 * OutboxRepositoryInterface - Contract for event persistence.
 *
 * Defines how domain events are persisted to the outbox,
 * separated from when and what events are emitted.
 */
interface OutboxRepositoryInterface
{
    /**
     * Persist a domain event to the outbox.
     *
     * @param DomainEvent $event The domain event to persist
     * @return EventOutbox The persisted outbox record
     */
    public function store(DomainEvent $event): EventOutbox;

    /**
     * Get pending events (for future async processing).
     *
     * @param int $limit Maximum number of events to retrieve
     * @return \Illuminate\Support\Collection
     */
    public function getPending(int $limit = 100): \Illuminate\Support\Collection;

    /**
     * Get failed events (for monitoring/alerting).
     *
     * @param int $limit Maximum number of events to retrieve
     * @return \Illuminate\Support\Collection
     */
    public function getFailed(int $limit = 100): \Illuminate\Support\Collection;
}
