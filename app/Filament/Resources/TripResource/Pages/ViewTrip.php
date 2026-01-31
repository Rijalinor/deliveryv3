<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Actions;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use App\Services\TripRouteGenerator;


class ViewTrip extends ViewRecord
{
    protected static string $resource = TripResource::class;


    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Ringkasan Trip')
                ->schema([
                    Grid::make(5)->schema([

                        TextEntry::make('stops_total')
                            ->label('Total')
                            ->state(fn($record) => $record->stops()->count())
                            ->badge(),

                        TextEntry::make('stops_done')
                            ->label('Done')
                            ->state(fn($record) => $record->stops()->where('status', 'done')->count())
                            ->badge(),

                        TextEntry::make('stops_skipped')
                            ->label('Skipped')
                            ->state(fn($record) => $record->stops()->where('status', 'skipped')->count())
                            ->badge(),

                        TextEntry::make('stops_remaining')
                            ->label('Sisa')
                            ->state(fn($record) => $record->stops()->whereIn('status', ['pending', 'arrived'])->count())
                            ->badge(),

                        TextEntry::make('status')
                            ->label('Status Trip')
                            ->state(fn($record) => $record->status)
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'planned' => 'gray',
                                'on_going' => 'warning',
                                'done' => 'success',
                                default => 'gray',
                            }),


                    ]),
                ])
                ->collapsed(false),

            Section::make('Map & Rute')
                ->schema([
                    ViewEntry::make('map')
                    ->label('')
                    ->state(fn ($record) => $record) // kirim Trip record
                    ->view('filament.components.trip-map'),
                ]),
        ]);
    }





    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('start')
                ->label('Mulai Trip')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->visible(fn() => $this->record->status === 'planned')
                ->action(function () {
                    $this->record->update(['status' => 'on_going']);
                    $this->notify('success', 'Trip dimulai');
                }),

            Actions\Action::make('generate_ors')
                ->label('Generate Route (ORS)')
                ->icon('heroicon-o-map')
                ->color('success')
                ->action(function () {
                    \App\Jobs\GenerateTripRouteJob::dispatch($this->record);

                    Notification::make()
                        ->title('Route generation started in background')
                        ->body('Please wait a moment and refresh the page.')
                        ->success()
                        ->send();
                }),
            ...parent::getHeaderActions(),

            Actions\Action::make('finish')
                ->label('Selesaikan Trip')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn() => $this->record->status !== 'done')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'done']);
                    $this->notify('success', 'Trip diselesaikan');
                }),

            Actions\Action::make('reset')
                ->label('Reset ke Planned')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn() => $this->record->status !== 'planned')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'planned']);
                    $this->notify('success', 'Trip di-reset ke planned');
                }),
        ];
    }
}
