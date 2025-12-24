<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
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
        //
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
    }
}
