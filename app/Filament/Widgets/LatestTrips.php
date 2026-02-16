<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TripResource;
use App\Models\Trip;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTrips extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Trip::query()
                    ->with(['driver', 'stops'])
                    ->latest()
                    ->limit(10)
            )
            ->heading('Latest Trips')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Driver')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start Time')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('stops_count')
                    ->label('Stops')
                    ->counts('stops')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planned' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_distance_m')
                    ->label('Distance')
                    ->formatStateUsing(fn ($state) => $state ? round($state / 1000, 1).' km' : '-')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Trip $record): string => TripResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-m-eye')
                    ->color('primary'),
            ]);
    }
}
