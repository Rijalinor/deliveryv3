<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use App\Services\OrsService;
use App\Services\TripRouteGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TripRouteGeneratorEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_throws_when_no_valid_stop_coordinates(): void
    {
        $trip = $this->makeTrip();

        $store = Store::create([
            'name' => 'Toko Nol',
            'address' => 'Alamat Test',
            'lat' => 0.0,
            'lng' => 0.0,
            'close_time' => '23:59:00',
        ]);

        TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $ors = Mockery::mock(OrsService::class);
        $generator = new TripRouteGenerator($ors);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stops kosong');

        $generator->generate($trip->fresh());
    }

    private function makeTrip(): Trip
    {
        $driver = User::factory()->create();

        return Trip::create([
            'driver_id' => $driver->id,
            'start_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'start_lat' => 1.000000,
            'start_lng' => 101.000000,
            'status' => 'planned',
        ]);
    }
}
