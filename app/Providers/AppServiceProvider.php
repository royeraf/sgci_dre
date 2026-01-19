<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        // Fix for MySQL key length on shared hosting (cPanel)
        // 191 * 4 bytes (utf8mb4) = 764 bytes < 1000 byte limit
        Schema::defaultStringLength(191);

        // Force HTTPS in production (cPanel deployment)
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
