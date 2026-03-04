<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_login_and_receive_token(): void
    {
        $driver = User::factory()->create([
            'email' => 'driver@example.com',
            'password' => bcrypt('password123'),
        ]);
        \Spatie\Permission\Models\Role::create(['name' => 'driver']);
        $driver->assignRole('driver'); // Ensure driver role

        $response = $this->postJson('/api/auth/login', [
            'email' => 'driver@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);
            
        $this->assertTrue($response->json('success'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $driver = User::factory()->create([
            'email' => 'driver@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'driver@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    public function test_non_driver_cannot_login_to_api(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $admin->assignRole('admin');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'UNAUTHORIZED_ROLE',
            ]);
    }

    public function test_driver_can_logout(): void
    {
        \Spatie\Permission\Models\Role::create(['name' => 'driver']);
        $driver = User::factory()->create();
        $driver->assignRole('driver');
        $token = $driver->createToken('driver-app')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $driver->id,
        ]);
    }
}
