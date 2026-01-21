<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripStopsSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_trip_stops_for_selected_stores(): void
    {
        $trip = $this->makeTrip();
        $stores = [
            $this->makeStore('Toko A'),
            $this->makeStore('Toko B'),
            $this->makeStore('Toko C'),
        ];

        foreach ($stores as $store) {
            TripStop::create([
                'trip_id' => $trip->id,
                'store_id' => $store->id,
                'status' => 'pending',
            ]);
        }

        $this->assertSame(3, $trip->stops()->count());
        $this->assertSame(3, $trip->stops()->where('status', 'pending')->count());
    }

    public function test_edit_trip_sync_adds_and_soft_deletes_stops(): void
    {
        $trip = $this->makeTrip();

        $storeA = $this->makeStore('Toko A');
        $storeB = $this->makeStore('Toko B');
        $storeC = $this->makeStore('Toko C');

        TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $storeA->id,
            'status' => 'pending',
        ]);

        TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $storeB->id,
            'status' => 'pending',
        ]);

        $newIds = collect([$storeB->id, $storeC->id])->unique()->values();
        $oldIds = $trip->stops()->whereNull('deleted_at')->pluck('store_id');

        $toDelete = $oldIds->diff($newIds);
        TripStop::where('trip_id', $trip->id)
            ->whereIn('store_id', $toDelete)
            ->whereNull('deleted_at')
            ->delete();

        $toAdd = $newIds->diff($oldIds);
        foreach ($toAdd as $storeId) {
            TripStop::create([
                'trip_id' => $trip->id,
                'store_id' => $storeId,
                'status' => 'pending',
            ]);
        }

        $this->assertSame(2, $trip->stops()->whereNull('deleted_at')->count());
        $this->assertSame(1, TripStop::withTrashed()->where('trip_id', $trip->id)->where('store_id', $storeA->id)->count());
        $this->assertNotNull(
            TripStop::withTrashed()->where('trip_id', $trip->id)->where('store_id', $storeA->id)->first()->deleted_at
        );
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
