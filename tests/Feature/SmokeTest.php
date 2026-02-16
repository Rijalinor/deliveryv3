<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup necessary roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'driver', 'guard_name' => 'web']);
    }

    public function test_homepage_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(302);
    }

    public function test_login_pages_are_accessible(): void
    {
        $this->get('/admin/login')->assertStatus(200);
        $this->get('/driver/login')->assertStatus(200);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(200);
    }

    public function test_admin_can_access_resources(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $routes = [
            '/admin/drivers',
            '/admin/stores',
            '/admin/trips',
            '/admin/users',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertStatus(200);
        }
    }

    public function test_driver_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('driver');

        $this->actingAs($user)
            ->get('/driver')
            ->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/driver')->assertRedirect('/driver/login');
    }
}
