<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api-read', function (Request $request) {
            return Limit::perMinute(120)->by($this->rateKey($request));
        });

        RateLimiter::for('api-write', function (Request $request) {
            return Limit::perMinute(30)->by($this->rateKey($request));
        });

        RateLimiter::for('api-payments', function (Request $request) {
            return Limit::perMinute(10)->by($this->rateKey($request));
        });
    }

    protected function rateKey(Request $request): string
    {
        $userId   = optional($request->user())->id ?? 'guest';
        $clinicId = app()->bound('currentClinicId')
            ? app('currentClinicId')
            : 'no-clinic';

        return "{$userId}|{$clinicId}|{$request->ip()}";
    }
}
