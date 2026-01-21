<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripStopUniqueConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_cannot_have_duplicate_store(): void
    {
        $trip = $this->makeTrip();
        $store = $this->makeStore('Toko A');

        TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $this->expectException(QueryException::class);

        TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $store->id,
            'status' => 'pending',
        ]);
    }

    private function makeTrip(): Trip
    {
        $driver = User::factory()->create();

        return Trip::create([
            'driver_id' => $driver->id,
            'start_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'start_lat' => 0.1,
            'start_lng' => 0.2,
            'status' => 'planned',
        ]);
    }

    private function makeStore(string $name): Store
    {
        return Store::create([
            'name' => $name,
            'address' => 'Alamat Test',
            'lat' => 1.2345678,
            'lng' => 2.3456789,
            'close_time' => '23:59:00',
        ]);
    }
}
