<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripStop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TripApiController extends Controller
{
    /**
     * Mark stop as arrived
     * 
     * @param Trip $trip
     * @param TripStop $stop
     * @return \Illuminate\Http\JsonResponse
     */
    public function markArrived(Trip $trip, TripStop $stop)
    {
        try {
            $driver = Auth::user();
            
            // Verify ownership
            if ($trip->driver_id !== $driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Verify stop belongs to trip
            if ($stop->trip_id !== $trip->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stop does not belong to this trip',
                ], 400);
            }

            // Check if already arrived or completed
            if (in_array($stop->status, ['arrived', 'done', 'skipped', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stop already processed',
                ], 400);
            }

            DB::transaction(function () use ($trip, $stop) {
                // Update stop status
                $stop->update([
                    'status' => 'arrived',
                    'arrived_at' => now(),
                ]);

                // Update trip status to on_going if still planned
                if ($trip->status === 'planned') {
                    $trip->update(['status' => 'on_going']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Marked as arrived',
                'data' => [
                    'stop_id' => $stop->id,
                    'status' => 'arrived',
                    'arrived_at' => $stop->arrived_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as arrived',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark stop as done (completed successfully)
     * 
     * @param Trip $trip
     * @param TripStop $stop
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markDone(Trip $trip, TripStop $stop, Request $request)
    {
        try {
            $driver = Auth::user();
            
            // Verify ownership
            if ($trip->driver_id !== $driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Verify stop belongs to trip
            if ($stop->trip_id !== $trip->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stop does not belong to this trip',
                ], 400);
            }

            // Validate notes if provided
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::transaction(function () use ($stop, $request) {
                $stop->update([
                    'status' => 'done',
                    'done_at' => now(),
                    'notes' => $request->notes ?? $stop->notes,
                ]);
            });

            $stop->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Delivery completed successfully',
                'data' => [
                    'stop_id' => $stop->id,
                    'status' => 'done',
                    'departed_at' => $stop->done_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as done',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark stop as rejected (failed delivery)
     * 
     * @param Trip $trip
     * @param TripStop $stop
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markRejected(Trip $trip, TripStop $stop, Request $request)
    {
        try {
            $driver = Auth::user();
            
            // Verify ownership
            if ($trip->driver_id !== $driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Verify stop belongs to trip
            if ($stop->trip_id !== $trip->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stop does not belong to this trip',
                ], 400);
            }

            // Validate rejection reason
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rejection reason is required',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::transaction(function () use ($stop, $request) {
                $stop->update([
                    'status' => 'rejected',
                    'skipped_at' => now(),
                    'notes' => $request->reason,
                ]);
            });

            $stop->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Delivery marked as rejected',
                'data' => [
                    'stop_id' => $stop->id,
                    'status' => 'rejected',
                    'reason' => $request->reason,
                    'departed_at' => $stop->skipped_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as rejected',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finish the entire trip
     * 
     * @param Trip $trip
     * @return \Illuminate\Http\JsonResponse
     */
    public function finishTrip(Trip $trip)
    {
        try {
            $driver = Auth::user();
            
            // Verify ownership
            if ($trip->driver_id !== $driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Check if trip can be finished
            if ($trip->status === 'done') {
                return response()->json([
                    'success' => false,
                    'message' => 'Trip already finished',
                ], 400);
            }

            $totalStops = $trip->stops()->count();
            $completedStops = $trip->stops()
                ->whereIn('status', ['done', 'rejected', 'skipped'])
                ->count();

            if ($completedStops < $totalStops) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot finish trip. Some stops are still pending.',
                    'data' => [
                        'total_stops' => $totalStops,
                        'completed_stops' => $completedStops,
                        'remaining_stops' => $totalStops - $completedStops,
                    ],
                ], 400);
            }

            $trip->update(['status' => 'done']);

            return response()->json([
                'success' => true,
                'message' => 'Trip finished successfully',
                'data' => [
                    'trip_id' => $trip->id,
                    'status' => 'done',
                    'total_stops' => $totalStops,
                    'done_stops' => $trip->stops()->where('status', 'done')->count(),
                    'rejected_stops' => $trip->stops()->where('status', 'rejected')->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to finish trip',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
