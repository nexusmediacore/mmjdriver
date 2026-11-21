<?php

namespace App\Providers;

use App\Models\TripRequest;
use App\Policies\TripRequestPolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected array $policies = [
        TripRequest::class => TripRequestPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
