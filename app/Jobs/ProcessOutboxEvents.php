<?php

namespace App\Jobs;

use App\Services\OutboxEventConsumer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * ProcessOutboxEvents Job - Background worker for event outbox consumption.
 *
 * This job processes pending events from the outbox table, ensuring:
 * - At-least-once delivery
 * - Controlled retries
 * - Idempotent processing
 *
 * Can be dispatched manually or scheduled via cron.
 */
class ProcessOutboxEvents implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    private ?int $batchSize;

    /**
     * Create a new job instance.
     *
     * @param int|null $batchSize Number of events to process (null = use default)
     */
    public function __construct(?int $batchSize = null)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     */
    public function handle(OutboxEventConsumer $consumer): void
    {
        Log::channel('api')->info('Starting outbox processing job', [
            'batch_size' => $this->batchSize ?? 'default',
        ]);

        $stats = $consumer->processPendingEvents($this->batchSize);

        Log::channel('api')->info('Outbox processing job completed', $stats);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('api')->error('Outbox processing job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

