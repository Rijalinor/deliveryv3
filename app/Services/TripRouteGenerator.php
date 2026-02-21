<?php

namespace App\Services;

use App\Models\Trip;

class TripRouteGenerator
{
    public function __construct(private OrsService $ors) {}

    public function generate(Trip $trip): void
    {
        $trip->loadMissing(['stops.store']);

        $stops = $trip->stops
            ->filter(fn ($s) => $s->store && $s->store->lat && $s->store->lng)
            ->values();

        if ($stops->isEmpty()) {
            throw new \RuntimeException('Stops kosong / koordinat toko belum lengkap.');
        }

        // Reset sequence lama sebelum generate baru
        $trip->stops()->update([
            'sequence' => null,
            'eta_at' => null,
            'close_at' => null,
        ]);

        $trip->load(['stops.store']);

        // === Konfigurasi Aturan ===
        $bufferMinutes = 10;
        $trafficFactor = (float) ($trip->traffic_factor ?? 1.30);

        $bufferSec = $bufferMinutes * 60;
        $defaultServiceSec = (int) ($trip->service_minutes ?? 5) * 60;

        $startTime = $trip->start_time ?? '08:00:00';
        $startTimeSec = $this->timeToSec($startTime);
        $startCoord = [(float) $trip->start_lng, (float) $trip->start_lat];

        // 1. Optimization (Vrp / TSP) - Mencari urutan terbaik (Fokus Jarak/Geografis + Time Windows)
        $jobs = [];
        foreach ($stops as $stop) {
            $storeServiceSec = ($stop->store->service_minutes !== null) 
                ? ($stop->store->service_minutes * 60) 
                : $defaultServiceSec;

            $openSec = $this->timeToSec($stop->store->open_time ?? '08:00:00');
            $closeSec = $this->timeToSec($stop->store->close_time ?? '23:59:00');

            $jobs[] = [
                'id' => (int) $stop->id,
                'location' => [(float) $stop->store->lng, (float) $stop->store->lat],
                'service' => $storeServiceSec,
                'time_windows' => [
                    [$openSec, $closeSec]
                ],
            ];
        }

        $optimizationResult = $this->ors->optimize($startCoord, $jobs, $startTimeSec);
        $steps = data_get($optimizationResult, 'routes.0.steps', []);
        $unassigned = data_get($optimizationResult, 'unassigned', []);

        if (empty($steps) && empty($unassigned)) {
            throw new \RuntimeException('ORS optimization gagal menentukan rute.');
        }

        // Mapping urutan (sequence) berdasarkan hasil optimization
        $assignedStopIds = [];
        $sequence = 1;

        \Illuminate\Support\Facades\DB::transaction(function () use ($steps, $unassigned, $stops, &$sequence, &$assignedStopIds) {
            // 1. Assign sequence untuk yang sukses di-optimize
            foreach ($steps as $step) {
                $jobId = data_get($step, 'job');
                if (! $jobId) {
                    continue;
                }

                $stop = $stops->firstWhere('id', (int) $jobId);
                if ($stop) {
                    \Illuminate\Support\Facades\DB::table('trip_stops')
                        ->where('id', $stop->id)
                        ->update([
                            'sequence' => $sequence++,
                            'updated_at' => now(),
                        ]);
                    $assignedStopIds[] = $stop->id;
                }
            }

            // 2. Handle unassigned (jika ada yang tidak bisa dijangkau/unreachable)
            foreach ($unassigned as $u) {
                $jobId = data_get($u, 'id');
                $stop = $stops->firstWhere('id', (int) $jobId);
                if ($stop && ! in_array($stop->id, $assignedStopIds)) {
                    \Illuminate\Support\Facades\DB::table('trip_stops')
                        ->where('id', $stop->id)
                        ->update([
                            'sequence' => $sequence++,
                            'updated_at' => now(),
                        ]);
                    $assignedStopIds[] = $stop->id;
                    
                    $reason = data_get($u, 'reason', 'Unreachable');
                    \Illuminate\Support\Facades\Log::warning("Stop #{$stop->id} ({$stop->store->name}) UNASSIGNED oleh ORS. Reason: {$reason}");
                }
            }
        });

        // 2. High-Precision Matrix (untuk ETA dan Jarak Akurat antar segmen)
        $orderedStops = $trip->stops()
            ->with('store')
            ->whereNotNull('sequence')
            ->orderBy('sequence')
            ->get();

        $points = [$startCoord];
        foreach ($orderedStops as $s) {
            $points[] = [(float) $s->store->lng, (float) $s->store->lat];
        }

        $matrix = $this->ors->matrix($points);
        $durations = data_get($matrix, 'durations', []);
        $distances = data_get($matrix, 'distances', []);

        if (empty($durations) || empty($distances)) {
            throw new \RuntimeException('Gagal mengambil data matrix (durasi/jarak).');
        }

        $currentSec = $startTimeSec;
        $totalDistance = 0;

        foreach ($orderedStops as $idx => $stop) {
            $fromIdx = $idx;
            $toIdx = $idx + 1;

            $segmentDuration = (int) (($durations[$fromIdx][$toIdx] ?? 0) * $trafficFactor);
            $segmentDistance = (float) ($distances[$fromIdx][$toIdx] ?? 0);

            $totalDistance += $segmentDistance;
            $arrivalSec = $currentSec + $segmentDuration;

            // Pastikan ETA tidak mendahului jam buka (tunggu kalau kepagian)
            $openSec = $this->timeToSec($stop->store->open_time ?? '08:00:00');
            $actualArrivalSec = max($arrivalSec, $openSec);
            
            $closeSec = $this->timeToSec($stop->store->close_time ?? '23:59:00');

            $stop->update([
                'eta_at' => $this->etaAtFromTripDate($trip->start_date, $actualArrivalSec),
                'close_at' => $this->etaAtFromTripDate($trip->start_date, $closeSec),
            ]);

            // Waktu keberangkatan dari toko ini: Kedatangan Aktif + Waktu Layanan
            $storeServiceSec = ($stop->store->service_minutes !== null) 
                ? ($stop->store->service_minutes * 60) 
                : $defaultServiceSec;
                
            $currentSec = $actualArrivalSec + $storeServiceSec;
        }

        // Hitung juga jalur pulang ke gudang agar total distance/duration akurat
        $lastToWarehouseDuration = (int) (($durations[count($orderedStops)][0] ?? 0) * $trafficFactor);
        $lastToWarehouseDistance = (float) ($distances[count($orderedStops)][0] ?? 0);
        $totalDistance += $lastToWarehouseDistance;
        $currentSec += $lastToWarehouseDuration;

        // 3. Final Directions GeoJSON (untuk tampilan Map)
        $coords = [$startCoord];
        foreach ($orderedStops as $s) {
            $coords[] = [(float) $s->store->lng, (float) $s->store->lat];
        }
        $coords[] = $startCoord; // Kembali ke gudang

        $geojson = $this->ors->directions($coords, $trip->ors_profile ?? config('delivery.ors_profile', 'driving-car'));

        $trip->update([
            'generated_at' => now(),
            'total_distance_m' => (int) $totalDistance,
            'total_duration_s' => (int) ($currentSec - $startTimeSec),
            'route_geojson' => json_encode($geojson),
        ]);
    }

    private function etaAtFromTripDate($tripDate, int $secFromMidnight): string
    {
        $secFromMidnight = max(0, $secFromMidnight);

        $h = intdiv($secFromMidnight, 3600);
        $m = intdiv($secFromMidnight % 3600, 60);
        $s = $secFromMidnight % 60;

        if ($tripDate === null) {
            $date = now()->format('Y-m-d');
        } elseif (is_string($tripDate)) {
            $date = $tripDate;
        } else {
            $date = $tripDate->format('Y-m-d');
        }

        return sprintf('%s %02d:%02d:%02d', $date, $h, $m, $s);
    }

    private function timeToSec(string $time): int
    {
        // time format: HH:MM atau HH:MM:SS
        $parts = explode(':', $time);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        $s = (int) ($parts[2] ?? 0);

        return $h * 3600 + $m * 60 + $s;
    }

    private function secToTime(int $sec): string
    {
        $sec = max(0, $sec);
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        $s = $sec % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
