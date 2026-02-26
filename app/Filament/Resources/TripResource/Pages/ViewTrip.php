<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use App\Services\TripRouteGenerator;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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
                            ->label('Total Toko')
                            ->state(fn ($record) => $record->stops()->count())
                            ->icon('heroicon-o-shopping-bag')
                            ->color('primary')
                            ->badge(),

                        TextEntry::make('stops_done')
                            ->label('Selesai')
                            ->state(fn ($record) => $record->stops()->where('status', 'done')->count())
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->badge(),

                        TextEntry::make('stops_skipped')
                            ->label('Reject')
                            ->state(fn ($record) => $record->stops()->whereIn('status', ['skipped', 'rejected'])->count())
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->badge(),

                        TextEntry::make('stops_remaining')
                            ->label('Sisa')
                            ->state(fn ($record) => $record->stops()->whereIn('status', ['pending', 'arrived'])->count())
                            ->icon('heroicon-o-clock')
                            ->color('warning')
                            ->badge(),

                        TextEntry::make('status')
                            ->label('Status Trip')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'planned' => 'gray',
                                'on_going' => 'warning',
                                'done' => 'success',
                                default => 'gray',
                            }),
                    ]),
                ]),

            Grid::make(3)->schema([
                Section::make('Informasi Dasar')
                    ->schema([
                        TextEntry::make('driver.name')
                            ->label('Driver')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('start_date')
                            ->label('Tanggal')
                            ->date('d M Y')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('start_time')
                            ->label('Mulai')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('ors_profile')
                            ->label('Mode Kendaraan')
                            ->badge()
                            ->icon('heroicon-o-truck'),
                        TextEntry::make('service_minutes')
                            ->label('Waktu di Toko')
                            ->suffix(' menit')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('traffic_factor')
                            ->label('Faktor Traffic')
                            ->state(fn ($record) => $record->traffic_factor.'x')
                            ->icon('heroicon-o-bolt'),
                    ])->columnSpan(1),

                Section::make('Detail Perjalanan')
                    ->schema([
                        TextEntry::make('total_distance_m')
                            ->label('Total Jarak')
                            ->state(function ($record) {
                                if (! $record->total_distance_m) {
                                    return '-';
                                }

                                return round($record->total_distance_m / 1000, 2).' km';
                            })
                            ->icon('heroicon-o-map'),
                        TextEntry::make('total_duration_s')
                            ->label('Estimasi Waktu')
                            ->state(function ($record) {
                                if (! $record->total_duration_s) {
                                    return '-';
                                }
                                $minutes = round($record->total_duration_s / 60);
                                if ($minutes < 60) {
                                    return $minutes.' menit';
                                }
                                $hours = floor($minutes / 60);
                                $min = $minutes % 60;

                                return "{$hours} jam {$min} menit";
                            })
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('estimated_fuel_cost')
                            ->label('Estimasi BBM')
                            ->state(function ($record) {
                                if (! $record->total_distance_m) {
                                    return '-';
                                }
                                $cost = $record->estimated_fuel_cost;

                                return 'Rp '.number_format($cost, 0, ',', '.');
                            })
                            ->icon('heroicon-o-fire')
                            ->color('warning')
                            ->helperText(function ($record) {
                                $kmPerLiter = config('delivery.fuel_km_per_liter', 10);
                                $price = config('delivery.fuel_price_per_liter', 13000);
                                $factor = config('delivery.fuel_safety_factor', 1.20);
                                $distKm = round(($record->total_distance_m ?? 0) / 1000, 1);

                                return "{$distKm} km ÷ {$kmPerLiter} km/L × Rp ".number_format($price, 0, ',', '.')." × {$factor}x safety";
                            }),
                        TextEntry::make('generated_at')
                            ->label('Rute Terakhir Dibuat')
                            ->dateTime('d M Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                    ])->columnSpan(1),

                Section::make('Catatan (Notice)')
                    ->schema([
                        TextEntry::make('notice')
                            ->label('')
                            ->placeholder('Tidak ada catatan khusus.')
                            ->markdown(),
                    ])->columnSpan(1),
            ]),

            Section::make('Map & Rute')
                ->schema([
                    ViewEntry::make('map')
                        ->label('')
                        ->state(fn ($record) => $record)
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
                ->visible(fn () => $this->record->status === 'planned')
                ->action(function () {
                    $this->record->update(['status' => 'on_going']);
                    $this->notify('success', 'Trip dimulai');
                }),

            Actions\Action::make('generate_ors')
                ->label('Generate Route (ORS)')
                ->icon('heroicon-o-map')
                ->color('success')
                ->action(function (TripRouteGenerator $gen) {
                    try {
                        $gen->generate($this->record);

                        Notification::make()
                            ->title('Route berhasil digenerate')
                            ->success()
                            ->send();

                        // Refresh halaman untuk menampilkan rute baru di map & stats
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
                ->visible(fn () => $this->record->status !== 'done')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'done']);
                    $this->notify('success', 'Trip diselesaikan');
                }),

            Actions\Action::make('reset')
                ->label('Reset ke Planned')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->record->status !== 'planned')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'planned']);
                    $this->notify('success', 'Trip di-reset ke planned');
                }),
        ];
    }
}
