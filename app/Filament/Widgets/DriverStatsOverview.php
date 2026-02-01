<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\TripStop;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DriverStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Total Completed Trips
        $totalTrips = Trip::where('status', 'done')->count();

        // 2. On-Time Rate (based on Stops)
        // Count all done stops
        $totalDoneStops = TripStop::where('status', 'done')->count();
        // Count stops that are NOT marked as late
        $onTimeStops = TripStop::where('status', 'done')
            ->where('is_late', false)
            ->count();

        $onTimeRate = $totalDoneStops > 0 
            ? round(($onTimeStops / $totalDoneStops) * 100, 1) 
            : 100;

        // 3. Avg Delivery Time (Arrived -> Done)
        // We can use the logic provided in TripStop::arrivedToFinishMinutes if available in query
        // For simplicity, let's take average of late_minutes just as a proxy for "Delay" or just total distance
        $totalDistanceKm = round(Trip::sum('total_distance_m') / 1000, 1);

        return [
            Stat::make('Total Trip Selesai', $totalTrips)
                ->description('Semua waktu')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),
            
            Stat::make('On-Time Delivery Rate', $onTimeRate . '%')
                ->description($onTimeStops . ' / ' . $totalDoneStops . ' stop tepat waktu')
                ->descriptionIcon('heroicon-m-clock')
                ->color($onTimeRate >= 90 ? 'success' : ($onTimeRate >= 70 ? 'warning' : 'danger')),

            Stat::make('Total Jarak Tempuh', $totalDistanceKm . ' km')
                ->description('Akumulasi semua driver')
                ->descriptionIcon('heroicon-m-map')
                ->color('info'),
        ];
    }
}
