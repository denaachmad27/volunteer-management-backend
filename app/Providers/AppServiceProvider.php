<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
        // Rate limiter untuk login: 5 percobaan/menit per kombinasi email+IP
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        // Rate limiter untuk register: 3 percobaan/menit per IP
        RateLimiter::for('register', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(3)->by($request->ip());
        });
    }
}
