<?php

namespace Tests\Feature;

use App\Filament\Driver\Resources\DriverTripResource\Pages\CreateDriverTrip;
use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\Store;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GiBasedTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Filament\Facades\Filament::setCurrentPanel(
            \Filament\Facades\Filament::getPanel('driver')
        );
    }

    public function test_driver_can_create_trip_using_multiple_gis()
    {
        // Mock ORS Service globally for this test
        $this->mock(\App\Services\OrsService::class, function ($mock) {
            $mock->shouldReceive('optimize')->andReturn([
                'routes' => [['distance' => 100, 'duration' => 200, 'steps' => [
                    ['type' => 'start'],
                    ['job' => 1, 'arrival' => 1000, 'service' => 0],
                    ['job' => 2, 'arrival' => 2000, 'service' => 0],
                ]]],
            ]);
            $mock->shouldReceive('matrix')->andReturn([
                'durations' => [[0, 100], [100, 0]],
                'distances' => [[0, 1000], [1000, 0]],
            ]);
            $mock->shouldReceive('directions')->andReturn([
                'type' => 'FeatureCollection',
                'features' => [],
            ]);
        });

        // 1. Setup Data
        $driver = User::factory()->create(['name' => 'Driver Budi']);
        $this->actingAs($driver);

        // GI 1
        $gi1 = GoodsIssue::create(['gi_number' => 'GI-001', 'date' => now(), 'status' => 'open']);
        GoodsIssueItem::create([
            'goods_issue_id' => $gi1->id,
            'pfi_number' => 'PFI-A1',
            'store_name' => 'Toko A',
            'address' => 'Jalan A',
            'amount' => 50000,
        ]);

        // GI 2 (Contains same store Toko A, so should merge)
        $gi2 = GoodsIssue::create(['gi_number' => 'GI-002', 'date' => now(), 'status' => 'open']);
        GoodsIssueItem::create([
            'goods_issue_id' => $gi2->id,
            'pfi_number' => 'PFI-A2', // Different PFI
            'store_name' => 'Toko A', // Same Store
            'address' => 'Jalan A',
            'amount' => 75000,
        ]);
        GoodsIssueItem::create([
            'goods_issue_id' => $gi2->id,
            'pfi_number' => 'PFI-B1',
            'store_name' => 'Toko B', // New Store
            'address' => 'Jalan B',
            'amount' => 30000,
        ]);

        // 2. Perform Create Action via Filament Page component logic simulation
        // Since testing Filament pages directly can be complex, let's call the Logic directly or use Livewire test
        // Let's rely on the Model/Service logic which is what HandleRecordCreation calls.

        $data = [
            'gi_input' => ['GI-001', 'GI-002'],
            'start_date' => now()->toDateString(),
            'start_time' => '08:00',
        ];

        // Simulate logic that is in CreateDriverTrip::handleRecordCreation
        // We can instantiate the page and call the method if public/protected, OR just recreate logic here for integration test
        // Better: Use Livewire test to fill form

        Livewire::test(CreateDriverTrip::class)
            ->fillForm([
                'gi_input' => ['GI-001', 'GI-002'],
                'start_date' => now()->toDateString(),
                'start_time' => '08:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // 3. Assertions
        $trip = Trip::latest()->first();
        $this->assertNotNull($trip);
        $this->assertEquals($driver->id, $trip->driver_id);
        $this->assertEquals('planned', $trip->status);
        $this->assertStringContainsString('GI-001', $trip->gi_number);
        $this->assertStringContainsString('GI-002', $trip->gi_number);

        // Check Stops (Should be 2 stops: Toko A and Toko B)
        $this->assertEquals(2, $trip->stops()->count());

        $stopA = $trip->stops()->whereHas('store', fn ($q) => $q->where('name', 'Toko A'))->first();
        $this->assertNotNull($stopA);
        // Stop A should have 2 invoices (PFI-A1 from GI1, PFI-A2 from GI2)
        // Access via relation if defined, or check DB
        $invoicesA = \App\Models\TripInvoice::where('trip_stop_id', $stopA->id)->get();
        $this->assertEquals(2, $invoicesA->count());
        $this->assertTrue($invoicesA->contains('pfi_number', 'PFI-A1'));
        $this->assertTrue($invoicesA->contains('pfi_number', 'PFI-A2'));

        // Check GIs updated
        $gi1->refresh();
        $gi2->refresh();
        $this->assertEquals('assigned', $gi1->status);
        $this->assertEquals('assigned', $gi2->status);
        $this->assertEquals($trip->id, $gi1->trip_id);
        $this->assertEquals($trip->id, $gi2->trip_id);
    }
}
