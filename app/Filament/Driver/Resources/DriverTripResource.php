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
                ->helperText('Tidak boleh pilih toko yang sama dalam 1 trip.'),
    
            Forms\Components\Textarea::make('notice')
                ->label('Catatan Driver')
                ->placeholder('Tulis catatan untuk trip ini jika ada...')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('start_date')
                        ->label('Tanggal')
                        ->date('d M Y')
                        ->weight('bold')
                        ->size('lg')
                        ->icon('heroicon-m-calendar'),
                    
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('start_time')
                            ->label('Jam')
                            ->icon('heroicon-m-clock')
                            ->color('gray'),
                        
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'planned' => 'gray',
                                'on_going' => 'warning',
                                'done' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                    Tables\Columns\TextColumn::make('progress')
                        ->label('Progress Pengiriman')
                        ->state(function ($record) {
                            $total = $record->stops()->count();
                            $done = $record->stops()->where('status', 'done')->count();
                            return "Selesai: {$done}/{$total} Toko";
                        })
                        ->description(fn($record) => 
                            ($record->stops()->whereIn('status', ['pending', 'arrived'])->count() ?? 0) . ' sisa • ' . 
                            ($record->stops()->whereIn('status', ['skipped', 'rejected'])->count() ?? 0) . ' reject'
                        )
                        ->color('primary')
                        ->icon('heroicon-m-truck'),

                    Tables\Columns\TextColumn::make('notice')
                        ->label('Catatan')
                        ->icon('heroicon-m-document-text')
                        ->color('gray')
                        ->size('sm')
                        ->limit(40)
                        ->placeholder('Tidak ada catatan khusus.')
                        ->wrap(),
                ])->space(3),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('run')
                    ->label('Mulai Trip')
                    ->icon('heroicon-m-play-circle')
                    ->color('success')
                    ->button()
                    ->url(fn (Trip $record): string => Pages\RunDriverTrip::getUrl(['record' => $record]))
                    ->visible(fn (Trip $record) => in_array($record->status, ['planned', 'on_going'])),
                
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])->color('gray'),
            ])
            ->bulkActions([]);
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
