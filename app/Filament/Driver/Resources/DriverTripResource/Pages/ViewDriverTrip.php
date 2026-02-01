<?php

namespace App\Filament\Driver\Resources\DriverTripResource\Pages;

use App\Filament\Driver\Resources\DriverTripResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Services\TripRouteGenerator;
use Filament\Notifications\Notification;
use Filament\Actions;

class ViewDriverTrip extends ViewRecord
{
    protected static string $resource = DriverTripResource::class;

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
                            ->badge(),
                    ]),
                ]),

            Section::make('Map')
                ->schema([
                    ViewEntry::make('map')
                        ->label('')
                        ->state(fn($record) => $record)
                        ->view('filament.components.trip-map'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [

            Actions\Action::make('generate_ors')
                ->label('Generate Route (ORS)')
                ->icon('heroicon-o-map')
                ->color('success')
                ->action(function (TripRouteGenerator $gen) {
                    try {
                        $gen->generate($this->record->fresh());

                        Notification::make()
                            ->title('Route berhasil digenerate')
                            ->success()
                            ->send();

                        // Refresh halaman total agar map & stats terupdate presisi
                        return redirect(request()->header('Referer'));
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Generate gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
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

            Actions\Action::make('run')
                ->label('Mulai Trip')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->url(
                    fn($record) => \App\Filament\Driver\Resources\DriverTripResource::getUrl('run', ['record' => $record])
                ),


        ];
    }
}
