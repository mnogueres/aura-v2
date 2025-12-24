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
        $projector = app(\App\Projections\PatientTimelineProjector::class);

        Event::listen(\App\Events\CRM\PatientCreated::class, [$projector, 'handlePatientCreated']);
        Event::listen(\App\Events\Billing\InvoiceCreated::class, [$projector, 'handleInvoiceCreated']);
        Event::listen(\App\Events\Billing\InvoiceIssued::class, [$projector, 'handleInvoiceIssued']);
        Event::listen(\App\Events\Billing\InvoicePaid::class, [$projector, 'handleInvoicePaid']);
        Event::listen(\App\Events\Billing\PaymentRecorded::class, [$projector, 'handlePaymentRecorded']);
        Event::listen(\App\Events\Billing\PaymentApplied::class, [$projector, 'handlePaymentApplied']);
        Event::listen(\App\Events\Billing\PaymentUnlinked::class, [$projector, 'handlePaymentUnlinked']);
    }
}
