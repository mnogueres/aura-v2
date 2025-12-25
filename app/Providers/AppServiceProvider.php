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
        $billingTimelineProjector = app(\App\Projections\BillingTimelineProjector::class);
        $auditTrailProjector = app(\App\Projections\AuditTrailProjector::class);
        $clinicalVisitProjector = app(\App\Projections\ClinicalVisitProjector::class);
        $clinicalTreatmentProjector = app(\App\Projections\ClinicalTreatmentProjector::class);

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

        // Billing Timeline Projection
        Event::listen(\App\Events\Billing\InvoiceCreated::class, [$billingTimelineProjector, 'handleInvoiceCreated']);
        Event::listen(\App\Events\Billing\InvoiceIssued::class, [$billingTimelineProjector, 'handleInvoiceIssued']);
        Event::listen(\App\Events\Billing\InvoicePaid::class, [$billingTimelineProjector, 'handleInvoicePaid']);
        Event::listen(\App\Events\Billing\PaymentRecorded::class, [$billingTimelineProjector, 'handlePaymentRecorded']);
        Event::listen(\App\Events\Billing\PaymentApplied::class, [$billingTimelineProjector, 'handlePaymentApplied']);
        Event::listen(\App\Events\Billing\PaymentUnlinked::class, [$billingTimelineProjector, 'handlePaymentUnlinked']);

        // Audit Trail Projection
        Event::listen(\App\Events\Platform\RateLimited::class, [$auditTrailProjector, 'handleRateLimited']);
        Event::listen(\App\Events\Platform\IdempotencyReplayed::class, [$auditTrailProjector, 'handleIdempotencyReplayed']);
        Event::listen(\App\Events\Platform\IdempotencyConflict::class, [$auditTrailProjector, 'handleIdempotencyConflict']);

        // Clinical Projections
        Event::listen(\App\Events\Clinical\VisitRecorded::class, [$clinicalVisitProjector, 'handleVisitRecorded']);
        Event::listen(\App\Events\Clinical\TreatmentRecorded::class, [$clinicalTreatmentProjector, 'handleTreatmentRecorded']);
    }
}
