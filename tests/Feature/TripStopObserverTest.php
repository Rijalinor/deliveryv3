<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripStopObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_status_moves_to_on_going_when_a_stop_arrives(): void
    {
        $trip = $this->makeTrip();
        $store = $this->makeStore();

        $stop = TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $this->assertSame('planned', $trip->refresh()->status);

        $stop->update(['status' => 'arrived']);

        $this->assertSame('on_going', $trip->refresh()->status);
    }

    public function test_trip_status_moves_to_done_when_no_pending_or_arrived(): void
    {
        $trip = $this->makeTrip();

        $stopA = TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $this->makeStore()->id,
            'status' => 'pending',
        ]);

        $stopB = TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $this->makeStore()->id,
            'status' => 'pending',
        ]);

        $stopA->update(['status' => 'done']);
        $this->assertSame('on_going', $trip->refresh()->status);

        $stopB->update(['status' => 'skipped']);
        $this->assertSame('done', $trip->refresh()->status);
    }

    public function test_trip_status_resets_to_planned_when_all_stops_deleted(): void
    {
        $trip = $this->makeTrip();

        $stop = TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $this->makeStore()->id,
            'status' => 'pending',
        ]);

        $stop->delete();

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

    private function makeStore(): Store
    {
        return Store::create([
            'name' => 'Toko Test',
            'address' => 'Alamat Test',
            'lat' => 1.2345678,
            'lng' => 2.3456789,
            'close_time' => '23:59:00',
        ]);
    }
}
