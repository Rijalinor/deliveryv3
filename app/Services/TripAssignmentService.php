<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Exception;

class TripAssignmentService
{
    public function processGiBasedTrip(Trip $trip, array $giNumbers): void
    {
        DB::transaction(function () use ($trip, $giNumbers) {
            $existingStopsCount = $trip->stops()->count();
            $sequence = $existingStopsCount + 1;

            foreach ($giNumbers as $giNumber) {
                $gi = \App\Models\GoodsIssue::where('gi_number', $giNumber)
                    ->lockForUpdate()
                    ->first();

                if (!$gi) {
                    throw new Exception("GI Number {$giNumber} not found.");
                }

                if ($gi->status !== 'open') {
                    throw new Exception("GI {$giNumber} is already assigned or completed.");
                }

                // Update GI Status
                $gi->update([
                    'status' => 'assigned',
                    'trip_id' => $trip->id,
                ]);

                // Group items by store to create stops
                $groupedItems = $gi->items->groupBy(function ($item) {
                    return $item->store_id ?: trim($item->store_name);
                });

                foreach ($groupedItems as $storeName => $items) {
                    $firstItem = $items->first();
                    
                    // Resolve Store
                    $store = null;
                    if ($firstItem->store_id) {
                        $store = \App\Models\Store::find($firstItem->store_id);
                    }
                    if (!$store) {
                        $store = \App\Models\Store::firstOrCreate(
                            ['name' => $storeName],
                            [
                                'address' => $firstItem->address,
                                'lat' => config('delivery.warehouse_lat', -6.200000),
                                'lng' => config('delivery.warehouse_lng', 106.816666),
                            ]
                        );
                    }

                    // Create or Find Stop for this Trip + Store
                    // Logic: If trip already has this store, reuse the stop?
                    // Yes, user said "merge".
                    $stop = \App\Models\TripStop::where('trip_id', $trip->id)
                        ->where('store_id', $store->id)
                        ->first();

                    if (!$stop) {
                        $stop = \App\Models\TripStop::create([
                            'trip_id' => $trip->id,
                            'store_id' => $store->id,
                            'sequence' => $sequence++,
                            'status' => 'pending',
                        ]);
                    }

                    // Add Invoices
                    foreach ($items as $item) {
                        \App\Models\TripInvoice::create([
                            'trip_id' => $trip->id,
                            'trip_stop_id' => $stop->id,
                            'pfi_number' => $item->pfi_number ?? 'N/A',
                            'amount' => $item->amount,
                        ]);
                    }
                }
            }
        });
    }
}
