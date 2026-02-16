<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_verify_trip()
    {
        $driver = User::factory()->create();

        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
        ]);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'driver_id' => $driver->id,
        ]);
    }

    public function test_trip_can_have_multiple_stops()
    {
        $trip = Trip::factory()->create();
        $stores = Store::factory()->count(3)->create();

        foreach ($stores as $index => $store) {
            TripStop::factory()->create([
                'trip_id' => $trip->id,
                'store_id' => $store->id,
                'sequence' => $index + 1,
            ]);
        }

        $this->assertEquals(3, $trip->stops()->count());
    }
}
