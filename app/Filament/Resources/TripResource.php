<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Models\Store;
use App\Models\Trip;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Trips';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Trip Info')->schema([
                Forms\Components\Select::make('driver_id')
                    ->label('Driver')
                    ->options(fn () => User::role('driver')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->default(now()->toDateString()),

                Forms\Components\TimePicker::make('start_time')
                    ->seconds(false)
                    ->required()
                    ->default(now()->format('H:i')),

                Forms\Components\TextInput::make('start_lat')
                    ->label('Warehouse Lat (Start)')
                    ->numeric()
                    ->step('any')
                    ->default(config('delivery.warehouse_lat'))
                    ->required(),

                Forms\Components\TextInput::make('start_lng')
                    ->label('Warehouse Lng (Start)')
                    ->numeric()
                    ->step('any')
                    ->default(config('delivery.warehouse_lng'))
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'planned' => 'planned',
                        'on_going' => 'on_going',
                        'done' => 'done',
                        'cancelled' => 'cancelled',
                    ])->default('planned')
                    ->required(),
            ])->columns(3),

            Forms\Components\Section::make('Parameter Akurasi (ORS)')->schema([
                Forms\Components\TextInput::make('service_minutes')
                    ->label('Waktu per Toko (menit)')
                    ->numeric()
                    ->default(5)
                    ->suffix('menit')
                    ->helperText('Waktu rata-rata yang dihabiskan di lokasi toko.'),

                Forms\Components\TextInput::make('traffic_factor')
                    ->label('Faktor Traffic')
                    ->numeric()
                    ->step(0.01)
                    ->default(1.30)
                    ->helperText('Pengali waktu tempuh (1.0 = ideal, 1.3 = kota padat).'),

                Forms\Components\Select::make('ors_profile')
                    ->label('Tipe Kendaraan')
                    ->options([
                        'driving-car' => 'Mobil (Car)',
                        'driving-hgv' => 'Truk (HGV)',
                        'cycling-regular' => 'Sepeda / Motor',
                    ])
                    ->default('driving-hgv')
                    ->required(),
            ])->columns(3),

            Forms\Components\Section::make('Catatan Tambahan')->schema([
                Forms\Components\Textarea::make('notice')
                    ->label('Notice')
                    ->placeholder('Catatan dari driver')
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Stops (Pilih toko)')->schema([
                // Field ini tidak ada di tabel trips, jadi pakai state only
                Forms\Components\Select::make('store_ids')
                    ->label('Toko dalam trip')
                    ->multiple()
                    ->options(fn () => Store::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->helperText('Tidak boleh pilih toko yang sama dalam 1 trip.'),
            ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('driver.name')->label('Driver')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label('Start')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'planned' => 'gray',
                        'on_going' => 'warning',
                        'done' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Toko (Selesai/Total)')
                    ->state(function ($record) {
                        $total = $record->stops_total ?? 0;
                        $done = $record->stops_done ?? 0;
                        $skipped = $record->stops_skipped ?? 0;

                        return "{$done}/{$total} (".($done + $skipped).')';
                    })
                    ->description(fn ($record) => ($record->stops_remaining ?? 0).' sisa • '.($record->stops_skipped ?? 0).' reject')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('stops_done', $direction);
                    }),
                Tables\Columns\TextColumn::make('estimated_fuel_cost')
                    ->label('Estimasi BBM')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 0, ',', '.'))
                    ->description(fn ($record) => ($record->total_distance_m ? round($record->total_distance_m / 1000, 1).' km' : '0 km'))
                    ->color('success')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notice')
                    ->label('Notice')
                    ->limit(30)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Generated')
                    ->dateTime('d/m H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(function ($query) {
                return $query->with('driver')->withCount([
                    'stops as stops_total',
                    'stops as stops_done' => fn ($q) => $q->where('status', 'done'),
                    'stops as stops_skipped' => fn ($q) => $q->whereIn('status', ['skipped', 'rejected']),
                    'stops as stops_remaining' => fn ($q) => $q->whereIn('status', ['pending', 'arrived']),
                ]);
            })

            ->filters([
                //
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\TripResource\RelationManagers\StopsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'view' => Pages\ViewTrip::route('/{record}'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
