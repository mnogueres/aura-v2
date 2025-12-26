<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\ApiRequestLogger::class,
        ]);

        $middleware->alias([
            'idempotent' => \App\Http\Middleware\EnsureIdempotency::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->apiError(
                    'validation_error',
                    'The given data was invalid.',
                    422,
                    $e->errors()
                );
            }
            return null;
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->apiError(
                    'forbidden',
                    'You are not allowed to perform this action.',
                    403
                );
            }
            return null;
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->apiError(
                    'forbidden',
                    'You are not allowed to perform this action.',
                    403
                );
            }
            return null;
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->apiError(
                    'not_found',
                    'Resource not found.',
                    404
                );
            }
            return null;
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->apiError(
                    'not_found',
                    'Resource not found.',
                    404
                );
            }
            return null;
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->apiError(
                    'rate_limited',
                    'Too many requests. Please slow down.',
                    429,
                    [
                        'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                    ]
                );
            }
            return null;
        });
    })->create();
