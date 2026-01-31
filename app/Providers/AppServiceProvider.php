<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TripStop;
use App\Observers\TripStopObserver;
use Illuminate\Support\Facades\URL;

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
        TripStop::observe(TripStopObserver::class);
         URL::forceScheme('https');
    }
}
