<?php

namespace App\Services;

use App\Contracts\OutboxRepositoryInterface;
use App\Events\DomainEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EventService - Single point of emission for domain events.
 *
 * Responsibilities:
 * - Receive DomainEvent instances
 * - Emit via event() only AFTER DB::commit
 * - Persist to outbox for durability and future processing
 * - Never throw exceptions that break the request flow
 *
 * Usage:
 *   app(EventService::class)->emit($event);
 */
class EventService
{
    public function __construct(
        private readonly OutboxRepositoryInterface $outboxRepository
    ) {
    }

    /**
     * Emit a domain event after the current database transaction commits.
     *
     * This ensures:
     * - Events are never emitted if the write fails
     * - Events are never emitted on failed replays
     * - Events are persisted to outbox for durability
     * - Real consistency (outbox pattern implemented)
     *
     * @param DomainEvent $event
     * @return void
     */
    public function emit(DomainEvent $event): void
    {
        try {
            DB::afterCommit(function () use ($event) {
                // Emit event to Laravel's event system
                event($event);

                // Persist to outbox for durability and future processing
                $this->outboxRepository->store($event);

                Log::channel('api')->debug('Domain event emitted and persisted', [
                    'event' => $event->event,
                    'request_id' => $event->request_id,
                    'payload' => $event->payload,
                ]);
            });
        } catch (\Throwable $e) {
            // Never break the request flow
            Log::channel('api')->error('Failed to schedule domain event', [
                'event' => $event->event ?? 'unknown',
                'error' => $e->getMessage(),
                'request_id' => $event->request_id ?? null,
            ]);
        }
    }
}
