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

class TripRouteGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_updates_sequences_eta_and_route(): void
    {
        $trip = $this->makeTrip();
        $trip->update(['service_minutes' => 0, 'traffic_factor' => 1.0]);

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
                    'distance' => 1234,
                    'duration' => 2345,
                    'steps' => [
                        ['type' => 'start'],
                        ['job' => $stopB->id, 'arrival' => 8 * 3600 + 600, 'service' => 900],
                        ['job' => $stopA->id, 'arrival' => 8 * 3600 + 1800, 'service' => 900],
                    ],
                ]],
            ]);

        $ors->shouldReceive('matrix')
            ->once()
            ->andReturn([
                'durations' => [
                    [0, 1000, 2000],
                    [1000, 0, 1000],
                    [345, 1000, 0], // Total duration: 1000 + 1000 + 345 = 2345
                ],
                'distances' => [
                    [0, 500, 1000],
                    [500, 0, 500],
                    [234, 500, 0], // Total distance: 500 + 500 + 234 = 1234
                ],
            ]);

        $ors->shouldReceive('directions')
            ->once()
            ->andReturn([
                'type' => 'FeatureCollection',
                'features' => [],
            ]);

        $generator = new TripRouteGenerator($ors);
        $generator->generate($trip->fresh());

        $stopA = $stopA->refresh();
        $stopB = $stopB->refresh();
        $trip = $trip->refresh();

        $this->assertSame(1, $stopB->sequence);
        $this->assertSame(2, $stopA->sequence);
        $this->assertNotNull($stopA->eta_at);
        $this->assertNotNull($stopB->eta_at);
        $this->assertSame(1234, $trip->total_distance_m);
        $this->assertSame(2345, $trip->total_duration_s);
        $this->assertSame(
            json_encode(['type' => 'FeatureCollection', 'features' => []]),
            $trip->route_geojson
        );
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
