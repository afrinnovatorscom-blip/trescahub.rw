<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && $user->hasRole('super-administrator')) {
                return true;
            }

            return null;
        });

        RateLimiter::for('marketplace-sensitive', function (Request $request) {
            return Limit::perMinute(8)->by((string) $request->ip());
        });
    }
}
