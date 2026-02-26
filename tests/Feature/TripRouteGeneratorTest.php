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

    // ─── Edge Case Tests ──────────────────────────────────────────────────────

    public function test_generate_throws_if_trip_has_no_stops(): void
    {
        $trip = $this->makeTrip();
        $ors = Mockery::mock(OrsService::class);
        // ORS should NOT be called at all
        $ors->shouldNotReceive('optimize');

        $generator = new TripRouteGenerator($ors);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/[Ss]tops kosong|koordinat/i');
        $generator->generate($trip->fresh());
    }

    public function test_generate_skips_stops_with_missing_coordinates(): void
    {
        $trip = $this->makeTrip();

        // Store dengan koordinat 0 (falsy) — akan difilter oleh TripRouteGenerator
        // karena filter fn($s) => $s->store && $s->store->lat && $s->store->lng
        $storeInvalid = Store::create([
            'name' => 'Toko Koordinat Nol', 'address' => 'Test',
            'lat' => 0, 'lng' => 0, // lat=0 dianggap falsy di PHP → dilewati
        ]);
        TripStop::create([
            'trip_id'  => $trip->id,
            'store_id' => $storeInvalid->id,
            'status'   => 'pending',
        ]);

        $ors = Mockery::mock(OrsService::class);
        $ors->shouldNotReceive('optimize');

        $generator = new TripRouteGenerator($ors);

        $this->expectException(\RuntimeException::class);
        $generator->generate($trip->fresh());
    }

    public function test_generate_assigns_sequence_to_unassigned_stops(): void
    {
        // Ketika ORS mengembalikan stop sebagai "unassigned" (tidak bisa dijangkau),
        // generator tetap harus assign sequence agar stop tidak hilang dari daftar.
        $trip = $this->makeTrip();
        $trip->update(['service_minutes' => 0, 'traffic_factor' => 1.0]);

        $storeA = $this->makeStore('Toko A', 1.0, 101.0);
        $stopA = TripStop::create([
            'trip_id' => $trip->id, 'store_id' => $storeA->id, 'status' => 'pending',
        ]);

        $ors = Mockery::mock(OrsService::class);
        $ors->shouldReceive('optimize')->once()->andReturn([
            'routes'     => [['steps' => [['type' => 'start']]]], // no jobs in steps
            'unassigned' => [['id' => $stopA->id, 'reason' => 'Out of range']],
        ]);
        $ors->shouldReceive('matrix')->once()->andReturn([
            'durations' => [[0, 600], [600, 0]],
            'distances' => [[0, 1000], [1000, 0]],
        ]);
        $ors->shouldReceive('directions')->once()->andReturn([
            'type' => 'FeatureCollection', 'features' => [],
        ]);

        $generator = new TripRouteGenerator($ors);
        $generator->generate($trip->fresh());

        $this->assertSame(1, $stopA->refresh()->sequence, 'Unassigned stop harus tetap mendapat sequence');
    }

    public function test_generate_applies_traffic_factor_to_eta(): void
    {
        // ETA harus diperbesar sesuai traffic_factor
        $trip = $this->makeTrip();
        $trip->update(['service_minutes' => 0, 'traffic_factor' => 2.0]); // 2x traffic factor

        $storeA = $this->makeStore('Toko A', 1.0, 101.0);
        $stopA = TripStop::create([
            'trip_id' => $trip->id, 'store_id' => $storeA->id, 'status' => 'pending',
        ]);

        $rawDurationSec = 1800; // 30 min dari ORS

        $ors = Mockery::mock(OrsService::class);
        $ors->shouldReceive('optimize')->once()->andReturn([
            'routes' => [['steps' => [
                ['type' => 'start'],
                ['job' => $stopA->id, 'arrival' => 8 * 3600 + $rawDurationSec, 'service' => 0],
            ]]],
        ]);
        $ors->shouldReceive('matrix')->once()->andReturn([
            'durations' => [[0, $rawDurationSec], [$rawDurationSec, 0]],
            'distances' => [[0, 1000], [1000, 0]],
        ]);
        $ors->shouldReceive('directions')->once()->andReturn([
            'type' => 'FeatureCollection', 'features' => [],
        ]);

        $generator = new TripRouteGenerator($ors);
        $generator->generate($trip->fresh());

        $stopA = $stopA->refresh();
        $this->assertNotNull($stopA->eta_at);

        // ETA = 08:00 + (1800s * 2.0 factor) = 08:00 + 3600s = 09:00
        $etaHour = \Carbon\Carbon::parse($stopA->eta_at)->hour;
        $this->assertSame(9, $etaHour, 'Traffic factor 2x harus menggeser ETA sesuai (08:30 -> 09:00)');
    }
}
