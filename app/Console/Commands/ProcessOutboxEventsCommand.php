<?php

namespace App\Console\Commands;

use App\Services\OutboxEventConsumer;
use Illuminate\Console\Command;

/**
 * ProcessOutboxEventsCommand - Manual command to process outbox events.
 *
 * Usage:
 *   php artisan outbox:process
 *   php artisan outbox:process --batch=50
 *   php artisan outbox:process --continuous
 */
class ProcessOutboxEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:process
                            {--batch= : Number of events to process per batch}
                            {--continuous : Keep processing until no more pending events}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending events from the outbox table';

    /**
     * Execute the console command.
     */
    public function handle(OutboxEventConsumer $consumer): int
    {
        $batchSize = $this->option('batch') ? (int) $this->option('batch') : null;
        $continuous = $this->option('continuous');

        $this->info('Starting outbox event processing...');
        $this->newLine();

        $totalProcessed = 0;
        $totalFailed = 0;

        do {
            $pendingCount = $consumer->getPendingCount();

            if ($pendingCount === 0) {
                $this->info('No pending events to process.');
                break;
            }

            $this->info("Processing {$pendingCount} pending event(s)...");

            $stats = $consumer->processPendingEvents($batchSize);

            $totalProcessed += $stats['processed'];
            $totalFailed += $stats['failed'];

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Processed', $stats['processed']],
                    ['Failed', $stats['failed']],
                    ['Skipped', $stats['skipped']],
                    ['Total in batch', $stats['total']],
                ]
            );

            $this->newLine();

            if ($continuous && $consumer->getPendingCount() > 0) {
                $this->info('Continuing to next batch...');
                $this->newLine();
                sleep(1); // Small delay between batches
            }
        } while ($continuous && $consumer->getPendingCount() > 0);

        $this->newLine();
        $this->info("Processing complete!");
        $this->info("Total processed: {$totalProcessed}");
        $this->info("Total failed: {$totalFailed}");

        $failedCount = $consumer->getFailedCount();
        if ($failedCount > 0) {
            $this->warn("There are {$failedCount} permanently failed event(s).");
        }

        return self::SUCCESS;
    }
}

