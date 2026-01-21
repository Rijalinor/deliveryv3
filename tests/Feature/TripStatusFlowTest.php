<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_stays_on_going_while_any_stop_is_arrived(): void
    {
        $trip = $this->makeTrip();

        $stopA = TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $this->makeStore('Toko A')->id,
            'status' => 'pending',
        ]);

        $stopB = TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $this->makeStore('Toko B')->id,
            'status' => 'pending',
        ]);

        $stopA->update(['status' => 'done']);
        $stopB->update(['status' => 'arrived']);

        $this->assertSame('on_going', $trip->refresh()->status);

        $stopB->update(['status' => 'done']);

        $this->assertSame('done', $trip->refresh()->status);
    }

    public function test_trip_remains_planned_when_all_stops_pending(): void
    {
        $trip = $this->makeTrip();

        TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $this->makeStore('Toko A')->id,
            'status' => 'pending',
        ]);

        TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $this->makeStore('Toko B')->id,
            'status' => 'pending',
        ]);

        $this->assertSame('planned', $trip->refresh()->status);
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
