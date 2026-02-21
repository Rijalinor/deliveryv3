<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupDriverLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-driver-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $days = (int) config('delivery.driver_location_retention_days', 7);
        $count = \App\Models\DriverLocation::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$count} driver location records older than {$days} days.");
    }
}
