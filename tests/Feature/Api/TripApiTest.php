<?php

namespace Tests\Feature\Api;

use App\Models\Store;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripApiTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;

    private Trip $trip;

    private TripStop $stop;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'driver']);
        $this->driver = User::factory()->create();
        $this->driver->assignRole('driver');
        $this->token = $this->driver->createToken('driver-app')->plainTextToken;

        $this->trip = Trip::create([
            'driver_id' => $this->driver->id,
            'status' => 'planned',
            'start_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'start_lat' => 0.1,
            'start_lng' => 0.2,
        ]);

        $store = Store::create([
            'name' => 'Toko Api Test',
            'lat' => 0.1,
            'lng' => 0.2,
        ]);

        $this->stop = TripStop::create([
            'trip_id' => $this->trip->id,
            'store_id' => $store->id,
            'status' => 'pending',
        ]);
    }

    public function test_driver_can_mark_stop_arrived(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/trip/{$this->trip->id}/stop/{$this->stop->id}/arrived");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'arrived',
                ],
            ]);

        $this->assertDatabaseHas('trip_stops', [
            'id' => $this->stop->id,
            'status' => 'arrived',
        ]);

        // Observer should have changed trip status to on_going
        $this->assertDatabaseHas('trips', [
            'id' => $this->trip->id,
            'status' => 'on_going',
        ]);
    }

    public function test_driver_can_mark_stop_done(): void
    {
        // First mark as arrived to simulate real flow
        $this->stop->update(['status' => 'arrived']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/trip/{$this->trip->id}/stop/{$this->stop->id}/done", [
            'notes' => 'Terkirim dengan aman',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('trip_stops', [
            'id' => $this->stop->id,
            'status' => 'done',
        ]);
    }

    public function test_driver_can_mark_stop_rejected(): void
    {
        $this->stop->update(['status' => 'arrived']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/trip/{$this->trip->id}/stop/{$this->stop->id}/rejected", [
            'reason' => 'Toko Tutup',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('trip_stops', [
            'id' => $this->stop->id,
            'status' => 'rejected',
            'skip_reason' => 'Toko Tutup',
        ]);
    }

    public function test_driver_can_finish_trip_if_all_stops_completed(): void
    {
        // Mark the only stop as done
        $this->stop->update(['status' => 'done']);
        $this->trip->update(['status' => 'on_going']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/trip/{$this->trip->id}/finish");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('trips', [
            'id' => $this->trip->id,
            'status' => 'done',
        ]);
    }

    public function test_driver_cannot_finish_trip_if_stops_pending(): void
    {
        // Stop is still pending
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/trip/{$this->trip->id}/finish");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot finish trip. Some stops are still pending.',
            ]);

        $this->assertDatabaseHas('trips', [
            'id' => $this->trip->id,
            'status' => 'planned',
        ]);
    }

    public function test_cannot_access_other_drivers_trip(): void
    {
        $otherDriver = User::factory()->create();
        $otherDriver->assignRole('driver');
        $otherToken = $otherDriver->createToken('driver-app')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $otherToken",
        ])->postJson("/api/trip/{$this->trip->id}/stop/{$this->stop->id}/arrived");

        $response->assertStatus(403);
    }
}
