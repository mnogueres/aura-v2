<?php

use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\V1\Workspace\PatientSummaryController;
use App\Http\Controllers\Api\V1\Workspace\PatientTimelineController;
use App\Http\Controllers\Api\V1\Workspace\BillingTimelineController;
use App\Http\Controllers\Api\V1\Workspace\AuditTrailController;
use Illuminate\Support\Facades\Route;

Route::middleware(
    app()->environment('testing') ? [] : ['auth:sanctum']
)->group(function () {

    Route::prefix('v1')->group(function () {

        // Read endpoints - 120 req/min
        Route::middleware('throttle:api-read')->group(function () {
            Route::get('/patients', [PatientController::class, 'index']);
            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::get('/payments', [PaymentController::class, 'index']);

            // Workspace (Read-only) endpoints
            Route::get('/workspace/patients/{patientId}/summary', [PatientSummaryController::class, 'show']);
            Route::get('/workspace/patients/{patientId}/timeline', [PatientTimelineController::class, 'index']);
            Route::get('/workspace/patients/{patientId}/billing', [BillingTimelineController::class, 'index']);
            Route::get('/workspace/audit', [AuditTrailController::class, 'index']);
        });

        // Write endpoints - 30 req/min
        Route::middleware(['throttle:api-write', 'idempotent'])->group(function () {
            Route::post('/patients', [PatientController::class, 'store']);
            Route::post('/invoices', [InvoiceController::class, 'store']);
        });

        // Payments endpoint - 10 req/min (ultra restrictive)
        Route::middleware(['throttle:api-payments', 'idempotent'])->group(function () {
            Route::post('/payments', [PaymentController::class, 'store']);
        });

    });

});
