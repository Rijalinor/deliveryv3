<?php

namespace Tests\Feature\Api;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverLocationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_update_location(): void
    {
        \Spatie\Permission\Models\Role::create(['name' => 'driver']);
        $driver = User::factory()->create();
        $driver->assignRole('driver');
        $token = $driver->createToken('driver-app')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/driver/location', [
            'latitude' => -3.3190,
            'longitude' => 114.5900,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Location updated successfully',
            ]);

        $this->assertDatabaseHas('driver_locations', [
            'driver_id' => $driver->id,
            'lat' => -3.3190,
            'lng' => 114.5900,
        ]);
    }

    public function test_driver_location_update_validates_data(): void
    {
        \Spatie\Permission\Models\Role::create(['name' => 'driver']);
        $driver = User::factory()->create();
        $driver->assignRole('driver');
        $token = $driver->createToken('driver-app')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/driver/location', [
            'latitude' => 95.0, // Invalid lat (max 90)
            'longitude' => 200.0, // Invalid lng (max 180)
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_FAILED',
            ]);
    }

    public function test_unauthenticated_user_cannot_update_location(): void
    {
        $response = $this->postJson('/api/driver/location', [
            'latitude' => -3.3190,
            'longitude' => 114.5900,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'UNAUTHENTICATED',
            ]);
    }

    public function test_active_trip_location_is_updated_when_driver_updates_location(): void
    {
        \Spatie\Permission\Models\Role::create(['name' => 'driver']);
        $driver = User::factory()->create();
        $driver->assignRole('driver');
        $token = $driver->createToken('driver-app')->plainTextToken;

        $trip = Trip::create([
            'driver_id' => $driver->id,
            'status' => 'on_going',
            'start_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'start_lat' => 0.1,
            'start_lng' => 0.2,
        ]);

        $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/driver/location', [
            'latitude' => -3.5,
            'longitude' => 114.5,
        ]);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'current_lat' => -3.5,
            'current_lng' => 114.5,
        ]);
    }
}
