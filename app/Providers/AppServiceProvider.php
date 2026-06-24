<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Interfaces\WhatsAppServiceInterface::class,
            function () {
                return match (config('whatsapp.provider')) {
                    'fonnte' => new \App\Services\WhatsApp\FonnteService(),
                    // 'starsender' => new \App\Services\WhatsApp\StarsenderService(),  // nanti
                    default  => new \App\Services\WhatsApp\FonnteService(),
                };
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        // Login/2FA: maksimal 5 percobaan per menit, per (email + IP)
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')) . '|' . $request->ip();
            return [Limit::perMinute(5)->by($key)];
        });

        // API umum: 60 request per menit, per user (kalau login) atau IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
