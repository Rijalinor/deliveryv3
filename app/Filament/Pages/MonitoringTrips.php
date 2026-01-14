<?php

namespace App\Filament\Pages;

use App\Models\Trip;
use Filament\Pages\Page;

class MonitoringTrips extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Monitoring';
    protected static ?string $title = 'Monitoring Trips';
    protected static ?string $navigationGroup = 'Trips';
    protected static ?int $navigationSort = 1;

    protected static ?string $pollingInterval = '30s';

    protected static string $view = 'filament.pages.monitoring-trips';

    public function getViewData(): array
    {
        $trips = Trip::query()
            ->with(['driver:id,name', 'stops.store:id,name,lat,lng,address'])
            ->where('status', 'on_going')
            ->orderByDesc('id')
            ->take(12)
            ->get();

        return compact('trips');
    }
}
