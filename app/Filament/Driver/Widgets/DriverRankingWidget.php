<?php

namespace App\Filament\Driver\Widgets;

use App\Models\User;
use App\Models\TripStop;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class DriverRankingWidget extends BaseWidget
{
    protected static ?string $heading = '🏅 Peringkat Driver Terajin';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::role('driver')
                    ->select('users.*')
                    ->withCount(['trips'])
                    ->addSelect([
                        'points' => TripStop::select(DB::raw('COALESCE(SUM(CASE WHEN status = "done" THEN 10 WHEN status = "rejected" THEN -5 ELSE 0 END), 0)'))
                            ->whereHas('trip', fn($q) => $q->whereColumn('driver_id', 'users.id'))
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('No')
                    ->rowIndex(),
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Driver')
                    ->fontFamily('Outfit')
                    ->weight('bold')
                    ->description(fn ($record) => $record->trips_count . ' total trips'),
                Tables\Columns\TextColumn::make('points')
                    ->label('Poin')
                    ->alignEnd()
                    ->badge()
                    ->color('success')
                    ->sortable()
            ])
            ->defaultSort('points', 'desc')
            ->paginated(false);
    }
}
