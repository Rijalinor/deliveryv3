<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverLocation;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DriverApiController extends Controller
{
    /**
     * Update driver's current location
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $driver = Auth::user();

            // Create location record
            DriverLocation::create([
                'driver_id' => $driver->id,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
                'accuracy' => $request->accuracy ?? null,
                'recorded_at' => now(),
            ]);

            // Update trip's current location if there's an active trip
            $activeTrip = Trip::where('driver_id', $driver->id)
                ->whereIn('status', ['planned', 'on_going'])
                ->first();

            if ($activeTrip) {
                $activeTrip->update([
                    'current_lat' => $request->latitude,
                    'current_lng' => $request->longitude,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'has_active_trip' => $activeTrip !== null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get driver's active trip
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveTrip()
    {
        try {
            $driver = Auth::user();

            $trip = Trip::where('driver_id', $driver->id)
                ->whereIn('status', ['planned', 'on_going'])
                ->with(['stops' => function ($query) {
                    $query->orderBy('sequence');
                }, 'stops.store', 'stops.invoices'])
                ->first();

            if (! $trip) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active trip found',
                    'data' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $trip->id,
                    'status' => $trip->status,
                    'start_date' => $trip->start_date,
                    'start_time' => $trip->start_time,
                    'notice' => $trip->notice,
                    'total_distance_m' => $trip->total_distance_m,
                    'total_duration_s' => $trip->total_duration_s,
                    'route_geojson' => $trip->route_geojson ? json_decode($trip->route_geojson) : null,
                    'stops' => $trip->stops->map(function ($stop) {
                        return [
                            'id' => $stop->id,
                            'sequence' => $stop->sequence,
                            'status' => $stop->status,
                            'store' => [
                                'id' => $stop->store->id,
                                'name' => $stop->store->name,
                                'address' => $stop->store->address,
                                'latitude' => $stop->store->lat,
                                'longitude' => $stop->store->lng,
                            ],
                            'eta' => $stop->eta_at?->toIso8601String(),
                            'arrived_at' => $stop->arrived_at?->toIso8601String(),
                            'departed_at' => $stop->done_at?->toIso8601String() ?? $stop->skipped_at?->toIso8601String(),
                            'close_time' => $stop->close_at?->toIso8601String(),
                            'invoices_count' => $stop->invoices->count(),
                        ];
                    }),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active trip',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific trip details
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTripDetails(Trip $trip)
    {
        try {
            $driver = Auth::user();

            // Ensure driver owns this trip
            if ($trip->driver_id !== $driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this trip',
                ], 403);
            }

            $trip->load(['stops' => function ($query) {
                $query->orderBy('sequence');
            }, 'stops.store', 'stops.invoices']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $trip->id,
                    'status' => $trip->status,
                    'start_date' => $trip->start_date,
                    'start_time' => $trip->start_time,
                    'notice' => $trip->notice,
                    'total_distance_m' => $trip->total_distance_m,
                    'total_duration_s' => $trip->total_duration_s,
                    'route_geojson' => $trip->route_geojson ? json_decode($trip->route_geojson) : null,
                    'stops' => $trip->stops->map(function ($stop) {
                        return [
                            'id' => $stop->id,
                            'sequence' => $stop->sequence,
                            'status' => $stop->status,
                            'store' => [
                                'id' => $stop->store->id,
                                'name' => $stop->store->name,
                                'address' => $stop->store->address,
                                'latitude' => $stop->store->lat,
                                'longitude' => $stop->store->lng,
                            ],
                            'eta' => $stop->eta,
                            'arrived_at' => $stop->arrived_at,
                            'departed_at' => $stop->departed_at,
                            'close_time' => $stop->close_time,
                            'invoices' => $stop->invoices->map(function ($invoice) {
                                return [
                                    'id' => $invoice->id,
                                    'pfi_number' => $invoice->pfi_number,
                                    'amount' => $invoice->amount,
                                ];
                            }),
                        ];
                    }),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trip details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
