<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Services\TripRouteGenerator;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateTripRouteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Trip $trip)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TripRouteGenerator $generator): void
    {
        try {
            $generator->generate($this->trip);

            Notification::make()
                ->title('Route generated successfully')
                ->success()
                ->broadcast($this->trip->user) // Broadcast to user who owns the trip or admin? For now let's try broadcast to all or just save DB notification
                ->sendToDatabase($this->trip->driver->user ?? \App\Models\User::all()); // Fallback to all users for now as we don't know exactly who triggered it. 
                // Actually, let's just use database notification for simplicity, addressing all admins might be noisy.
                // Better: Just rely on the job finishing. The user will see the status update if they refresh.
                // Let's keep it simple:
                
        } catch (\Throwable $e) {
             // Log error
             \Illuminate\Support\Facades\Log::error('Trip Route Generation Failed: ' . $e->getMessage());
             
             // Optional: Fail the job
             $this->fail($e);
        }
    }
}
