<?php

namespace App\Observers;

use App\Models\TripStop;

class TripStopObserver
{
    /**
     * Jalankan setiap kali stop dibuat.
     */
    public function created(TripStop $tripStop): void
    {
        $this->syncTripStatus($tripStop);
    }

    /**
     * Jalankan setiap kali stop diupdate.
     */
    public function updated(TripStop $tripStop): void
    {
        $this->syncTripStatus($tripStop);
    }

    /**
     * Kalau stop dihapus (soft delete juga), sync ulang.
     */
    public function deleted(TripStop $tripStop): void
    {
        $this->syncTripStatus($tripStop);
    }

    private function syncTripStatus(TripStop $tripStop): void
    {
        $trip = $tripStop->trip;

        if (! $trip) {
            return;
        }

        // hanya hitung stop yang aktif (tidak ter-soft delete)
        $stops = $trip->stops()->get(['id', 'status']);

        if ($stops->isEmpty()) {
            // kalau trip belum punya stop, biarkan planned
            $trip->updateQuietly(['status' => 'planned']);

            return;
        }

        $pending = $stops->where('status', 'pending')->count();
        $arrived = $stops->where('status', 'arrived')->count();
        $done = $stops->where('status', 'done')->count();
        $skipped = $stops->where('status', 'skipped')->count();

        // RULE:
        // 1) done jika tidak ada pending & arrived
        if ($pending === 0 && $arrived === 0) {
            $newStatus = 'done';
        }
        // 2) on_going jika ada aktivitas (arrived/done/skipped)
        elseif (($arrived + $done + $skipped) > 0) {
            $newStatus = 'on_going';
        }
        // 3) selain itu planned
        else {
            $newStatus = 'planned';
        }

        if ($trip->status !== $newStatus) {
            // updateQuietly: biar tidak memicu event chain berlebihan
            $trip->updateQuietly(['status' => $newStatus]);
        }
    }
}
