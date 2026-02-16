<?php

namespace App\Filament\Driver\Resources\DriverTripResource\Pages;

use App\Filament\Driver\Resources\DriverTripResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;

class RunDriverTrip extends Page
{
    protected static string $resource = DriverTripResource::class;

    protected static string $view = 'filament.driver.run-trip';

    private const EARTH_RADIUS_METERS = 6371000;

    public $record; // Trip model

    // UI state
    public array $data = [
        'skip_reason' => null,

    ];

    public function mount($record): void
    {
        $this->record = DriverTripResource::getModel()::query()
            ->with(['stops.store'])
            ->findOrFail($record);

        // Optional: pastikan driver hanya akses trip miliknya
        // abort_unless($this->record->driver_id === auth()->id(), 403);

        // Auto start trip kalau masih planned
        if ($this->record->status === 'planned') {
            $this->record->update(['status' => 'on_going']);
            $this->record->refresh();
        }
        $this->dispatchMapToActiveStop();
        $this->form->fill([
            'skip_reason' => null,

        ]);
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->statePath('data')
            ->schema([
                \Filament\Forms\Components\Grid::make(12)->schema([
                    \Filament\Forms\Components\Select::make('skip_reason')
                        ->label('Alasan Reject / Gagal')
                        ->options([
                            'penerima_tidak_ada' => 'Penerima tidak ada',
                            'toko_tutup' => 'Toko tutup',
                            'barang_rusak' => 'Barang rusak',
                            'ditolak_penerima' => 'Ditolak penerima',
                            'lainnya' => 'Lainnya',
                        ])
                        ->native(false)
                        ->searchable()
                        ->required()
                        ->columnSpan(4),

                    \Filament\Forms\Components\TextInput::make('skip_note')
                        ->label('Catatan (opsional)')
                        ->placeholder('contoh: toko pindah / penerima minta besok')
                        ->maxLength(120)
                        ->columnSpan(8),
                ]),
            ]);
    }

    public function refreshTrip(): void
    {
        $this->record->refresh();
    }

    public function totalStops(): int
    {
        return $this->record->stops()->count();
    }

    public function doneStops(): int
    {
        return $this->record->stops()->whereIn('status', ['done', 'skipped', 'rejected'])->count();
    }

    public function activeStop()
    {
        return $this->record->stops()
            ->with('store')
            ->whereIn('status', ['pending', 'arrived'])
            ->orderBy('sequence')
            ->first();
    }

    public function progressText(): string
    {
        $total = $this->totalStops();
        $done = $this->doneStops();

        return "{$done} / {$total} selesai";
    }

    public function statusBadge($stop): array
    {
        if (! $stop) {
            return ['-', 'gray'];
        }

        $etaAt = $stop->eta_at ? Carbon::parse($stop->eta_at) : null;
        $closeAt = $stop->close_at ? Carbon::parse($stop->close_at) : null;

        if ($etaAt && $closeAt) {
            if ($etaAt->greaterThan($closeAt)) {
                return ['TELAT', 'danger'];
            }

            $diffMin = $closeAt->diffInMinutes($etaAt, false); // close - eta
            if ($diffMin <= 15) {
                return ['MEPET', 'warning'];
            }
        }

        return ['AMAN', 'success'];
    }

    private function dispatchMapToActiveStop(): void
    {
        $stop = $this->activeStop();

        if (! $stop || ! $stop->store) {
            // kalau sudah tidak ada stop aktif, kirim null biar map bisa kosong / tetap
            $this->dispatch('run-trip-refresh', dest: null);

            return;
        }

        $this->dispatch('run-trip-refresh', dest: [
            'lat' => (float) $stop->store->lat,
            'lng' => (float) $stop->store->lng,
            'name' => (string) $stop->store->name,
            'address' => (string) ($stop->store->address ?? ''),
        ]);
    }

    public function gmapsUrl(): string
    {
        $stop = $this->activeStop();
        if (! $stop || ! $stop->store) {
            return '#';
        }

        $lat = (float) $stop->store->lat;
        $lng = (float) $stop->store->lng;

        return 'https://www.google.com/maps/dir/?api=1'
            ."&destination={$lat},{$lng}"
            .'&travelmode=driving'
            .'&dir_action=navigate';
    }

    public function markArrived(): void
    {
        $stop = $this->activeStop();
        if (! $stop) {
            return;
        }

        if ($stop->status === 'pending') {
            $stop->update([
                'status' => 'arrived',
                'arrived_at' => now(),
            ]);

            Notification::make()->title('Status: Arrived')->success()->send();
        }

        $this->form->fill([
            'skip_reason' => null,

        ]);

        $this->refreshTrip();
        $this->dispatchMapToActiveStop();
        // untuk refresh map
    }

    public function updateDriverLocation(float $lat, float $lng): void
    {
        $stop = $this->activeStop();
        if (! $stop || ! in_array($stop->status, ['pending', 'arrived']) || ! $stop->store) {
            return;
        }

        $destLat = (float) $stop->store->lat;
        $destLng = (float) $stop->store->lng;
        if (! $destLat || ! $destLng) {
            return;
        }

        $radius = (int) config('delivery.auto_arrive_radius_meters', 100);
        $distance = $this->distanceMeters($lat, $lng, $destLat, $destLng);

        \Illuminate\Support\Facades\Log::info("Driver Loc: {$lat}, {$lng} | Dest: {$destLat}, {$destLng} | Dist: {$distance}m | Radius: {$radius}m | Status: {$stop->status}");

        // Simpan riwayat ke database
        \App\Models\DriverLocation::create([
            'driver_id' => auth()->id(),
            'trip_id' => $this->record->id,
            'lat' => $lat,
            'lng' => $lng,
        ]);

        // ✅ Simpan posisi terakhir ke tabel trips agar monitoring admin cepat (tanpa scan tabel history)
        $this->record->update([
            'current_lat' => $lat,
            'current_lng' => $lng,
        ]);

        if ($distance > $radius) {
            // Logic Auto-Done: Kalau status sudah 'arrived' dan menjauh > radius -> tandai DONE
            if ($stop->status === 'arrived') {
                \Illuminate\Support\Facades\Log::info("Auto-Done Triggered for Stop #{$stop->id}");
                $stop->update([
                    'status' => 'done',
                    'done_at' => now(),
                ]);

                Notification::make()->title('Stop selesai (Auto-Done)')->success()->send();
                $this->refreshTrip();
                $this->dispatchMapToActiveStop();
            }

            return;
        }

        // Logic Auto-Arrived
        if ($stop->status === 'pending') {
            \Illuminate\Support\Facades\Log::info("Auto-Arrived Triggered for Stop #{$stop->id}");
            $stop->update([
                'status' => 'arrived',
                'arrived_at' => $stop->arrived_at ?? now(),
            ]);

            Notification::make()->title('Status: Arrived (auto)')->success()->send();

            $this->refreshTrip();
            $this->dispatchMapToActiveStop();
        }
    }

    public function markDone(): void
    {
        $stop = $this->activeStop();
        if (! $stop) {
            return;
        }

        $stop->update([
            'status' => 'done',
            'done_at' => now(),
            // kalau kolom ada:
            // 'skip_reason' => $reason,
            // 'skip_note' => $note,
        ]);

        Notification::make()->title('Stop selesai (Done)')->success()->send();

        $this->form->fill([
            'skip_reason' => null,

        ]);

        $this->refreshTrip();
        $this->dispatchMapToActiveStop();
    }

    public function markRejected(): void
    {
        $stop = $this->activeStop();
        if (! $stop) {
            return;
        }

        $reason = $this->data['skip_reason'] ?? null;
        $note = trim((string) ($this->data['skip_note'] ?? ''));

        if (! $reason) {
            Notification::make()->title('Pilih alasan reject dulu')->danger()->send();

            return;
        }

        // gabungkan jadi 1 string
        $finalReason = $reason.($note !== '' ? (' | '.$note) : '');

        $stop->update([
            'status' => 'rejected', // Ganti skipped jadi rejected
            'skipped_at' => now(), // Kita pakai kolom skipped_at aja biar nggak ubah DB schema
            'skip_reason' => $finalReason,
        ]);

        Notification::make()->title('Pengiriman Ditolak (Rejected)')->danger()->send();

        $this->form->fill(['skip_reason' => null, 'skip_note' => null]);
        $this->refreshTrip();
        $this->dispatchMapToActiveStop();
    }

    public function finishTrip(): void
    {
        $still = $this->record->stops()
            ->whereIn('status', ['pending', 'arrived'])
            ->exists();

        if ($still) {
            Notification::make()->title('Masih ada stop yang belum selesai')->danger()->send();

            return;
        }

        $this->record->update(['status' => 'done']);
        $this->record->refresh();

        Notification::make()->title('Trip selesai ✅')->success()->send();
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);
        $deltaLat = $lat2 - $lat1;
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }
}
