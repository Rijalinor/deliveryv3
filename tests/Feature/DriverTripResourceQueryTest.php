<?php

namespace Tests\Feature;

use App\Filament\Driver\Resources\DriverTripResource;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTripResourceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_resource_only_shows_current_driver_planned_or_on_going(): void
    {
        $driverA = User::factory()->create();
        $driverB = User::factory()->create();

        $tripA1 = $this->makeTrip($driverA->id, 'planned');
        $tripA2 = $this->makeTrip($driverA->id, 'on_going');
        $this->makeTrip($driverA->id, 'done');
        $this->makeTrip($driverB->id, 'planned');

        $this->actingAs($driverA);

        $ids = DriverTripResource::getEloquentQuery()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$tripA1->id, $tripA2->id], $ids);
    }

    private function makeTrip(int $driverId, string $status): Trip
    {
        return Trip::create([
            'driver_id' => $driverId,
            'start_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'start_lat' => 0.1,
            'start_lng' => 0.2,
            'status' => $status,
        ]);
    }
}
