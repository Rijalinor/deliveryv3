<?php

namespace App\Filament\Driver\Widgets;

use App\Models\Trip;
use App\Models\TripStop;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TripStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $driverId = Auth::id();

        $totalTrips = Trip::where('driver_id', $driverId)->count();

        $completedStops = TripStop::whereHas('trip', function ($query) use ($driverId) {
            $query->where('driver_id', $driverId);
        })->where('status', 'done')->count();

        // Simple Productivity Score: (Done Stops / Total Assigned Stops) * 100
        $totalStops = TripStop::whereHas('trip', function ($query) use ($driverId) {
            $query->where('driver_id', $driverId);
        })->count();

        $score = $totalStops > 0 ? round(($completedStops / $totalStops) * 100) : 0;

        return [
            Stat::make('Total Trip', $totalTrips)
                ->description('Trip yang sudah dijalani')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
            Stat::make('Pesanan Terkirim', $completedStops)
                ->description('Total toko yang sudah selesai')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Skor Performa', $score.'%')
                ->description('Persentase keberhasilan')
                ->descriptionIcon('heroicon-m-star')
                ->color($score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger')),
        ];
    }
}
