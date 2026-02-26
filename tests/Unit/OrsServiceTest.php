<?php

namespace Tests\Unit;

use App\Services\OrsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrsServiceTest extends TestCase
{
    public function test_optimize_requires_api_key(): void
    {
        config(['services.ors.key' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ORS_API_KEY kosong');

        (new OrsService)->optimize([0.0, 0.0], [], 0);
    }

    public function test_optimize_returns_json_on_success(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/optimization' => Http::response([
                'routes' => [[
                    'distance' => 100,
                    'duration' => 200,
                    'steps' => [],
                ]],
            ], 200),
        ]);

        $result = (new OrsService)->optimize([1.0, 2.0], [], 0);

        $this->assertSame(100, $result['routes'][0]['distance']);
        $this->assertSame(200, $result['routes'][0]['duration']);
    }

    public function test_optimize_throws_on_error(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/optimization' => Http::response([
                'error' => ['code' => 123, 'message' => 'bad request'],
            ], 400),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ORS optimize error (123): bad request');

        (new OrsService)->optimize([1.0, 2.0], [], 0);
    }

    public function test_matrix_throws_on_error(): void
    {
        config(['services.ors.key' => 'test-key']);
        config(['ors.profile' => 'driving-car']);

        Http::fake([
            'https://api.openrouteservice.org/v2/matrix/driving-car' => Http::response([
                'error' => ['code' => 500, 'message' => 'oops'],
            ], 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ORS matrix error (500): oops');

        (new OrsService)->matrix([[1.0, 2.0], [3.0, 4.0]]);
    }

    public function test_directions_returns_json_on_success(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car/geojson' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [],
            ], 200),
        ]);

        $result = (new OrsService)->directions([[1.0, 2.0], [3.0, 4.0]]);

        $this->assertSame('FeatureCollection', $result['type']);
    }
}
