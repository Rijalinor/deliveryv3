<?php

namespace App\Providers;

use App\Models\TripStop;
use App\Observers\TripStopObserver;
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
        TripStop::observe(TripStopObserver::class);
        URL::forceScheme('https');

        // Override config with DB settings if table exists
        try {
            if (\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::all(['key', 'value']);
                foreach ($settings as $setting) {
                    config(["delivery.{$setting->key}" => $setting->value]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if DB is not ready
        }
    }
}
