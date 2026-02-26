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

        // Single query for all trip stats (avoids 4 separate DB round-trips)
        $stats = DB::table('trips')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN start_date = ? THEN 1 ELSE 0 END) as total_today,
                SUM(CASE WHEN status = 'on_going' THEN 1 ELSE 0 END) as active_now,
                SUM(CASE WHEN start_date = ? AND status = 'done' THEN 1 ELSE 0 END) as completed_today
            ", [now()->toDateString(), now()->toDateString()])
            ->first();

        $totalToday = $stats->total_today ?? 0;
        $activeNow = $stats->active_now ?? 0;
        $completedToday = $stats->completed_today ?? 0;
        $pendingStops = DB::table('trip_stops')
            ->where('status', 'pending')
            ->whereNull('deleted_at')
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
