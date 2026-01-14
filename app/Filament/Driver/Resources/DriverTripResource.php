<?php

namespace App\Filament\Driver\Resources;

use App\Filament\Driver\Resources\DriverTripResource\Pages;
use App\Filament\Driver\Resources\DriverTripResource\RelationManagers;
use App\Models\DriverTrip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;

class DriverTripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('start_date')
                ->required()
                ->default(now()->toDateString()),
    
            Forms\Components\TimePicker::make('start_time')
                ->required()
                ->seconds(false)
                ->default(now()->format('H:i')),
    
            // start lat/lng bisa auto dari config/env
            Forms\Components\Hidden::make('start_lat')
                ->default(config('delivery.warehouse_lat')),
    
            Forms\Components\Hidden::make('start_lng')
                ->default(config('delivery.warehouse_lng')),
    
            Forms\Components\Select::make('store_ids')
                ->label('Toko dalam trip')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(\App\Models\Store::query()->pluck('name', 'id'))
                ->required()
                ->helperText('Tidak boleh pilih toko yang sama dalam 1 trip.')
                 // jangan masuk ke kolom trips
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('start_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            ])
            ->bulkActions([])

            ->filters([
                //
            ])
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
            RelationManagers\StopsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('driver_id', Auth::id())
            ->whereIn('status', ['planned', 'on_going'])
            ->latest();
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverTrips::route('/'),
            'create' => Pages\CreateDriverTrip::route('/create'),
            'run'   => Pages\RunDriverTrip::route('/{record}/run'),
            'view' => Pages\ViewDriverTrip::route('/{record}'),
            'edit' => Pages\EditDriverTrip::route('/{record}/edit'),
        ];
    }
}
