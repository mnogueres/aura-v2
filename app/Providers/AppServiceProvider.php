<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        \App\Models\Patient::class => \App\Policies\PatientPolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\Payment::class => \App\Policies\PaymentPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\OutboxRepositoryInterface::class,
            \App\Repositories\OutboxRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Response::macro('api', function (
            mixed $data = null,
            int $status = 200,
            array $meta = []
        ): JsonResponse {
            return response()->json([
                'data' => $data,
                'meta' => $meta,
            ], $status);
        });

        Response::macro('apiError', function (
            string $code,
            string $message,
            int $status,
            array $details = []
        ): JsonResponse {
            return response()->json([
                'data' => null,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'details' => $details ?: null,
                ],
                'meta' => [
                    'request_id' => request()->header('X-Request-Id') ?? (string) Str::uuid(),
                ],
            ], $status);
        });

        // Register projection listeners
        $this->registerProjectionListeners();
    }

    /**
     * Register event listeners for projections.
     */
    protected function registerProjectionListeners(): void
    {
        $timelineProjector = app(\App\Projections\PatientTimelineProjector::class);
        $summaryProjector = app(\App\Projections\PatientSummaryProjector::class);

        // Patient Timeline Projection
        Event::listen(\App\Events\CRM\PatientCreated::class, [$timelineProjector, 'handlePatientCreated']);
        Event::listen(\App\Events\Billing\InvoiceCreated::class, [$timelineProjector, 'handleInvoiceCreated']);
        Event::listen(\App\Events\Billing\InvoiceIssued::class, [$timelineProjector, 'handleInvoiceIssued']);
        Event::listen(\App\Events\Billing\InvoicePaid::class, [$timelineProjector, 'handleInvoicePaid']);
        Event::listen(\App\Events\Billing\PaymentRecorded::class, [$timelineProjector, 'handlePaymentRecorded']);
        Event::listen(\App\Events\Billing\PaymentApplied::class, [$timelineProjector, 'handlePaymentApplied']);
        Event::listen(\App\Events\Billing\PaymentUnlinked::class, [$timelineProjector, 'handlePaymentUnlinked']);

        // Patient Summary Projection
        Event::listen(\App\Events\CRM\PatientCreated::class, [$summaryProjector, 'handlePatientCreated']);
        Event::listen(\App\Events\Billing\InvoiceCreated::class, [$summaryProjector, 'handleInvoiceCreated']);
        Event::listen(\App\Events\Billing\InvoicePaid::class, [$summaryProjector, 'handleInvoicePaid']);
        Event::listen(\App\Events\Billing\PaymentRecorded::class, [$summaryProjector, 'handlePaymentRecorded']);
        Event::listen(\App\Events\Billing\PaymentApplied::class, [$summaryProjector, 'handlePaymentApplied']);
        Event::listen(\App\Events\Billing\PaymentUnlinked::class, [$summaryProjector, 'handlePaymentUnlinked']);
    }
}
