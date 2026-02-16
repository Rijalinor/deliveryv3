<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TripStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        $totalToday = Trip::whereDate('start_date', $today)->count();
        $activeNow = Trip::where('status', 'in_progress')->count();
        $completedToday = Trip::whereDate('start_date', $today)
            ->where('status', 'completed')
            ->count();
        $pendingStops = DB::table('trip_stops')
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Total Trips Today', $totalToday)
                ->description('All trips scheduled for today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->chart([7, 12, 8, 15, 10, 18, $totalToday]),

            Stat::make('Active Deliveries', $activeNow)
                ->description('Currently in progress')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning')
                ->chart([3, 5, 4, 6, 5, 7, $activeNow]),

            Stat::make('Completed Today', $completedToday)
                ->description(round($totalToday > 0 ? ($completedToday / $totalToday) * 100 : 0).'% completion rate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([2, 4, 3, 8, 6, 12, $completedToday]),

            Stat::make('Pending Stops', $pendingStops)
                ->description('Awaiting delivery')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('gray')
                ->chart([15, 12, 18, 10, 14, 8, $pendingStops]),
        ];
    }
}
