<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use App\Models\Store;
use App\Models\TripStop;


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
                    ->options(fn() => User::role('driver')->pluck('name', 'id'))
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
                    ->label('Warehouse Lat')
                    ->default(config('delivery.warehouse_lat'))
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                Forms\Components\TextInput::make('start_lng')
                    ->label('Warehouse Lng')
                    ->default(config('delivery.warehouse_lng'))
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'planned' => 'planned',
                        'on_going' => 'on_going',
                        'done' => 'done',
                        'cancelled' => 'cancelled',
                    ])
                    ->default('planned')
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('Stops (Pilih toko)')->schema([
                // Field ini tidak ada di tabel trips, jadi pakai state only
                Forms\Components\Select::make('store_ids')
                    ->label('Toko dalam trip')
                    ->multiple()
                    ->options(fn() => Store::query()->orderBy('name')->pluck('name', 'id'))
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
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('generated_at')->dateTime('d M Y H:i')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stops_count')->counts('stops')->label('Stops')->sortable(),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->state(
                        fn($record) =>
                        "{$record->stops_done}/{$record->stops_total} selesai • {$record->stops_skipped} skip • {$record->stops_remaining} sisa"
                    )
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'planned',
                        'warning' => 'on_going',
                        'success' => 'done',
                    ]),


            ])
            ->modifyQueryUsing(function ($query) {
                return $query->withCount([
                    'stops as stops_total',
                    'stops as stops_done' => fn($q) => $q->where('status', 'done'),
                    'stops as stops_skipped' => fn($q) => $q->where('status', 'skipped'),
                    'stops as stops_remaining' => fn($q) => $q->whereIn('status', ['pending', 'arrived']),
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
