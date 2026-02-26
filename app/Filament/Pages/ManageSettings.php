<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.manage-settings';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?string $title = 'Pengaturan Sistem';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'warehouse_lat' => Setting::get('warehouse_lat', config('delivery.warehouse_lat')),
            'warehouse_lng' => Setting::get('warehouse_lng', config('delivery.warehouse_lng')),
            'arrival_radius_meters' => Setting::get('arrival_radius_meters', config('delivery.arrival_radius_meters')),
            'departure_radius_meters' => Setting::get('departure_radius_meters', config('delivery.departure_radius_meters')),
            'dwell_time_seconds' => Setting::get('dwell_time_seconds', config('delivery.dwell_time_seconds')),
            'fuel_price_per_liter' => Setting::get('fuel_price_per_liter', config('delivery.fuel_price_per_liter')),
            'fuel_km_per_liter' => Setting::get('fuel_km_per_liter', config('delivery.fuel_km_per_liter')),
            'fuel_safety_factor' => Setting::get('fuel_safety_factor', config('delivery.fuel_safety_factor')),
            'service_minutes' => Setting::get('service_minutes', config('delivery.service_minutes')),
            'traffic_factor' => Setting::get('traffic_factor', config('delivery.traffic_factor')),
            'ors_profile' => Setting::get('ors_profile', config('delivery.ors_profile')),
            'location_retention_days' => Setting::get('location_retention_days', config('delivery.location_retention_days')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('Titik Gudang')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                TextInput::make('warehouse_lat')
                                    ->label('Latitude Gudang')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('warehouse_lng')
                                    ->label('Longitude Gudang')
                                    ->numeric()
                                    ->required(),
                            ]),
                        Tabs\Tab::make('Geofencing')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                TextInput::make('arrival_radius_meters')
                                    ->label('Radius Kedatangan (Meter)')
                                    ->numeric()
                                    ->suffix('meter')
                                    ->required(),
                                TextInput::make('departure_radius_meters')
                                    ->label('Radius Keberangkatan (Meter)')
                                    ->numeric()
                                    ->suffix('meter')
                                    ->required(),
                                TextInput::make('dwell_time_seconds')
                                    ->label('Waktu Tunggu Kedatangan (Detik)')
                                    ->numeric()
                                    ->suffix('detik')
                                    ->required(),
                            ]),
                        Tabs\Tab::make('Biaya BBM')
                            ->icon('heroicon-o-fire')
                            ->schema([
                                TextInput::make('fuel_price_per_liter')
                                    ->label('Harga BBM per Liter')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                TextInput::make('fuel_km_per_liter')
                                    ->label('Konsumsi BBM (KM/Liter)')
                                    ->numeric()
                                    ->suffix('km/L')
                                    ->required(),
                                TextInput::make('fuel_safety_factor')
                                    ->label('Faktor Safety BBM')
                                    ->numeric()
                                    ->step(0.01)
                                    ->helperText('Pengali untuk antisipasi macet (misal 1.20)')
                                    ->required(),
                            ]),
                        Tabs\Tab::make('Parameter Trip')
                            ->icon('heroicon-o-truck')
                            ->schema([
                                TextInput::make('service_minutes')
                                    ->label('Waktu Layanan per Toko')
                                    ->numeric()
                                    ->suffix('menit')
                                    ->required(),
                                TextInput::make('traffic_factor')
                                    ->label('Faktor Traffic Rute')
                                    ->numeric()
                                    ->step(0.01)
                                    ->helperText('Pengali waktu tempuh (misal 1.30)')
                                    ->required(),
                                Select::make('ors_profile')
                                    ->label('Profil Kendaraan Default')
                                    ->options([
                                        'driving-car' => 'Mobil (Car)',
                                        'driving-hgv' => 'Truk (HGV)',
                                        'cycling-regular' => 'Sepeda / Motor',
                                    ])
                                    ->required(),
                            ]),
                        Tabs\Tab::make('Sistem')
                            ->icon('heroicon-o-server')
                            ->schema([
                                TextInput::make('location_retention_days')
                                    ->label('Retensi Data Lokasi (Hari)')
                                    ->numeric()
                                    ->suffix('hari')
                                    ->helperText('Data GPS driver lama akan dihapus otomatis setelah X hari.')
                                    ->required(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Perubahan')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                Setting::set($key, $value);
            }

            Notification::make()
                ->title('Pengaturan berhasil disimpan')
                ->success()
                ->send();
        } catch (\Exception $exception) {
            Notification::make()
                ->title('Gagal menyimpan pengaturan')
                ->danger()
                ->send();
        }
    }
}
