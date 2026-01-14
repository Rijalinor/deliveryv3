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

        // === konfigurasi aturan ===
        $bufferMinutes = 10;     // warning mepet
        $serviceMinutes = (int) ($trip->service_minutes ?? 15);     // asumsi bongkar/serah terima per toko (boleh kamu ubah)
        $bufferSec = $bufferMinutes * 60;
        $serviceSec = $serviceMinutes * 60;

        // start time (dari trip_date + start_time)
        // kalau start_time null, anggap 08:00
        $startTime = $trip->start_time ?? '08:00:00';
        $startTimeSec = $this->timeToSec($startTime);

        // start coordinate gudang
        $start = [(float) $trip->start_lng, (float) $trip->start_lat];

        // buat jobs untuk ORS optimization
        // IMPORTANT: job.id harus integer unik
        $jobs = [];
        foreach ($stops as $i => $stop) {
            $close = $stop->store->close_time ?? '23:59:00';
            $closeSec = $this->timeToSec($close);

            // time window: boleh dikunjungi dari start sampai (close - buffer)
            $latestArrival = max($startTimeSec, $closeSec - $bufferSec);

            $jobs[] = [
                'id' => (int) $stop->id, // pakai stop_id biar gampang mapping
                'location' => [(float) $stop->store->lng, (float) $stop->store->lat],
                'service' => $serviceSec,
                'time_windows' => [[$startTimeSec, $latestArrival]],
            ];
        }

        // call ORS optimization (1 request)
        $result = $this->ors->optimize($start, $jobs, $startTimeSec);

        $steps = data_get($result, 'routes.0.steps', []);
        if (empty($steps)) {
            throw new \RuntimeException('ORS optimization tidak mengembalikan steps.');
        }


        // step pertama biasanya "start", sisanya job steps
        $sequence = 1;
        $currentSec = $startTimeSec; // waktu mulai trip dalam detik dari 00:00

        foreach ($steps as $step) {
            $jobId = data_get($step, 'job');
            if (! $jobId) {
                continue;
            }

            $stop = $stops->firstWhere('id', (int) $jobId);
            if (! $stop) continue;

            // 1) coba ambil arrival dari ORS (kalau ada)
            $arrivalSec = data_get($step, 'arrival');


            // 2) fallback: hitung arrival dari durasi step
            if ($arrivalSec === null) {
                $travelSec = (int) data_get($step, 'duration', 0);

                // kalau duration kosong, fallback kasar 10 menit
                if ($travelSec <= 0) {
                    $travelSec = 600;
                }

                $arrivalSec = $currentSec + $travelSec;
            }



            // warning mepet
            $closeSec = $this->timeToSec($stop->store->close_time ?? '23:59:00');
            $mepet = (($closeSec - (int) $arrivalSec) <= $bufferSec);



            $stop->update([
                'sequence' => $sequence++,
                // ✅ ini yang benar sesuai DB kamu
            ]);



            // update current time: arrival + service
            $service = (int) data_get($step, 'service', $serviceSec); // kalau ORS kirim service, pakai itu
            if ($service <= 0) $service = $serviceSec;

            $currentSec = (int) $arrivalSec + $service;
        }


        // simpan summary (opsional)
        $trip->update([
            'generated_at' => now(),
            'total_distance_m' => data_get($result, 'routes.0.distance'),
            'total_duration_s' => data_get($result, 'routes.0.duration'),
        ]);

        $orderedStops = $trip->stops()
            ->with('store')
            ->whereNotNull('sequence')
            ->orderBy('sequence')
            ->get();

        if ($orderedStops->isEmpty()) {
            throw new \RuntimeException('Tidak ada stop ber-sequence. Generate urutan dulu.');
        }

        $coords = [
            [(float) $trip->start_lng, (float) $trip->start_lat],
        ];

        // ===============================
        // ETA PRESISI: ORS MATRIX (1 request)
        // ===============================
        $points = [
            [(float) $trip->start_lng, (float) $trip->start_lat],
        ];

        foreach ($orderedStops as $s) {
            $points[] = [(float) $s->store->lng, (float) $s->store->lat];
        }
        // coords sudah berisi gudang + stop urut (lng,lat)

        // pastikan semua point valid [lng, lat] numeric
        $points = array_values(array_filter($points, function ($p) {
            if (!is_array($p) || count($p) !== 2) return false;

            $lng = $p[0];
            $lat = $p[1];

            if (!is_numeric($lng) || !is_numeric($lat)) return false;

            $lng = (float) $lng;
            $lat = (float) $lat;

            // range valid koordinat bumi
            if ($lng < -180 || $lng > 180) return false;
            if ($lat < -90 || $lat > 90) return false;

            return true;
        }));

        if (count($points) < 2) {
            throw new \RuntimeException('Matrix butuh minimal 2 lokasi (gudang + 1 toko). Cek koordinat trip/store.');
        }


        // pastikan semua point valid [lng, lat] numeric
        $points = array_values(array_filter($points, function ($p) {
            if (!is_array($p) || count($p) !== 2) return false;

            $lng = $p[0];
            $lat = $p[1];

            if (!is_numeric($lng) || !is_numeric($lat)) return false;

            $lng = (float) $lng;
            $lat = (float) $lat;

            // range valid koordinat bumi
            if ($lng < -180 || $lng > 180) return false;
            if ($lat < -90 || $lat > 90) return false;

            return true;
        }));

        if (count($points) < 2) {
            throw new \RuntimeException('Matrix butuh minimal 2 lokasi (gudang + 1 toko). Cek koordinat trip/store.');
        }


        $matrix = $this->ors->matrix($points);
        $durations = data_get($matrix, 'durations', []);

        if (!is_array($durations) || empty($durations)) {
            throw new \RuntimeException('ORS matrix tidak mengembalikan durations.');
        }

        $currentSec = $startTimeSec;

        foreach ($orderedStops as $idx => $stop) {
            $from = $idx;      // 0->1, 1->2, ...
            $to   = $idx + 1;

            $trafficFactor = 1.35; // kota + logistik
            $travelSec = (int) (($durations[$from][$to] ?? 0) * $trafficFactor);

            if ($travelSec <= 0) $travelSec = 600;

            $arrivalSec = $currentSec + $travelSec;

            $closeSec = $this->timeToSec($stop->store->close_time ?? '23:59:00');

            $stop->update([
                'eta_at' => $this->etaAtFromTripDate($trip->trip_date, $arrivalSec),
                'close_at' => $this->etaAtFromTripDate($trip->trip_date, $closeSec),
            ]);
            

            $microDelaySec = 5 * 60; // 5 menit
            $currentSec = $arrivalSec + $serviceSec + $microDelaySec;
            
        }


        foreach ($orderedStops as $stop) {
            $coords[] = [(float) $stop->store->lng, (float) $stop->store->lat];
        }



        $geojson = $this->ors->directions($coords);

        $trip->update([
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
