<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LatestTrips;
use App\Filament\Widgets\TripStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            TripStatsOverview::class,
            LatestTrips::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 12;
    }
}
