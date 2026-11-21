<?php

namespace App\Providers;

use App\Channels\WhatsAppChannel;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Registrasi channel notifikasi WhatsApp kustom
        Notification::extend('whatsapp', fn ($app) => $app->make(WhatsAppChannel::class));
    }
}
