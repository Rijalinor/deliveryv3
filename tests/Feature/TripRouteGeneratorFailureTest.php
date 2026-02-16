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

class TripRouteGeneratorFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_throws_when_optimize_returns_no_steps(): void
    {
        $trip = $this->makeTrip();
        $store = $this->makeStore('Toko A', 1.000001, 101.000001);

        TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $ors = Mockery::mock(OrsService::class);
        $ors->shouldReceive('optimize')
            ->once()
            ->andReturn([
                'routes' => [[
                    'steps' => [],
                ]],
            ]);

        $generator = new TripRouteGenerator($ors);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ORS optimization gagal menentukan rute.');

        $generator->generate($trip->fresh());
    }

    public function test_generate_throws_when_matrix_returns_empty_durations(): void
    {
        $trip = $this->makeTrip();

        $storeA = $this->makeStore('Toko A', 1.000001, 101.000001);
        $storeB = $this->makeStore('Toko B', 1.000002, 101.000002);

        $stopA = TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $storeA->id,
            'status' => 'pending',
        ]);

        $stopB = TripStop::create([
            'trip_id' => $trip->id,
            'store_id' => $storeB->id,
            'status' => 'pending',
        ]);

        $ors = Mockery::mock(OrsService::class);
        $ors->shouldReceive('optimize')
            ->once()
            ->andReturn([
                'routes' => [[
                    'steps' => [
                        ['type' => 'start'],
                        ['job' => $stopA->id, 'arrival' => 8 * 3600 + 600, 'service' => 900],
                        ['job' => $stopB->id, 'arrival' => 8 * 3600 + 1200, 'service' => 900],
                    ],
                ]],
            ]);

        $ors->shouldReceive('matrix')
            ->once()
            ->andReturn([
                'durations' => [],
                'distances' => [],
            ]);

        $generator = new TripRouteGenerator($ors);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gagal mengambil data matrix (durasi/jarak).');

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

    private function makeStore(string $name, float $lat, float $lng): Store
    {
        return Store::create([
            'name' => $name,
            'address' => 'Alamat Test',
            'lat' => $lat,
            'lng' => $lng,
            'close_time' => '23:59:00',
        ]);
    }
}
