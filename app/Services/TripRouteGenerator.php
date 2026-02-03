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
            ->filter(fn($s) => $s->store && $s->store->lat && $s->store->lng)
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
        $serviceMinutes = (int) ($trip->service_minutes ?? 5);
        $trafficFactor = (float) ($trip->traffic_factor ?? 1.30);
        
        $bufferSec = $bufferMinutes * 60;
        $serviceSec = $serviceMinutes * 60;

        $startTime = $trip->start_time ?? '08:00:00';
        $startTimeSec = $this->timeToSec($startTime);
        $startCoord = [(float) $trip->start_lng, (float) $trip->start_lat];

        // 1. Optimization (Vrp / TSP) - Mencari urutan terbaik
        $jobs = [];
        foreach ($stops as $stop) {
            $closeSec = $this->timeToSec($stop->store->close_time ?? '23:59:00');
            $latestArrival = max($startTimeSec, $closeSec - $bufferSec);

            $jobs[] = [
                'id' => (int) $stop->id,
                'location' => [(float) $stop->store->lng, (float) $stop->store->lat],
                'service' => $serviceSec,
                'time_windows' => [[$startTimeSec, $latestArrival]],
            ];
        }

        $optimizationResult = $this->ors->optimize($startCoord, $jobs, $startTimeSec);
        $steps = data_get($optimizationResult, 'routes.0.steps', []);

        if (empty($steps)) {
            throw new \RuntimeException('ORS optimization gagal menentukan urutan.');
        }

        // Mapping urutan (sequence) berdasarkan hasil optimization
        $sequence = 1;
        foreach ($steps as $step) {
            $jobId = data_get($step, 'job');
            if (!$jobId) continue;

            $stop = $stops->firstWhere('id', (int) $jobId);
            if ($stop) {
                $stop->update(['sequence' => $sequence++]);
            }
        }

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
            
            $closeSec = $this->timeToSec($stop->store->close_time ?? '23:59:00');

            $stop->update([
                'eta_at' => $this->etaAtFromTripDate($trip->start_date, $arrivalSec),
                'close_at' => $this->etaAtFromTripDate($trip->start_date, $closeSec),
            ]);

            // Waktu keberangkatan dari toko ini: Kedatangan + Waktu Layanan
            $currentSec = $arrivalSec + $serviceSec;
        }

        // 3. Final Directions GeoJSON (untuk tampilan Map)
        $coords = [$startCoord];
        foreach ($orderedStops as $s) {
            $coords[] = [(float) $s->store->lng, (float) $s->store->lat];
        }
        $coords[] = $startCoord; // Kembali ke gudang

        $geojson = $this->ors->directions($coords, $trip->ors_profile ?? 'driving-car');
        
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
