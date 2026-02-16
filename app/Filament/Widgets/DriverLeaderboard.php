<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DriverLeaderboard extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '🏅 Leaderboard Driver Terbaik';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    // Filter only users who have done at least one trip (simple logic)
                    ->whereHas('trips', function ($q) {
                        $q->where('status', 'done');
                    })
                    ->withCount(['trips' => function ($q) {
                        $q->where('status', 'done');
                    }])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Driver')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('trips_count')
                    ->label('Total Trip')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('on_time_rate')
                    ->label('On-Time Rate')
                    ->getStateUsing(function (User $record) {
                        $totalStops = TripStop::whereHas('trip', function ($q) use ($record) {
                            $q->where('driver_id', $record->id);
                        })->where('status', 'done')->count();

                        if ($totalStops === 0) {
                            return '-';
                        }

                        $onTimeStops = TripStop::whereHas('trip', function ($q) use ($record) {
                            $q->where('driver_id', $record->id);
                        })
                            ->where('status', 'done')
                            ->where('is_late', false)
                            ->count();

                        return round(($onTimeStops / $totalStops) * 100, 1).'%';
                    })
                    ->badge()
                    ->color(fn (string $state): string => (float) $state >= 90 ? 'success' :
                        ((float) $state >= 70 ? 'warning' : 'danger')
                    ),

                Tables\Columns\TextColumn::make('total_dist')
                    ->label('Jarak Tempuh')
                    ->getStateUsing(function (User $record) {
                        $m = Trip::where('driver_id', $record->id)
                            ->where('status', 'done')
                            ->sum('total_distance_m');

                        return round($m / 1000, 1).' km';
                    }),
            ])
            ->defaultSort('trips_count', 'desc');
    }
}
