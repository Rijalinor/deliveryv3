<x-filament::page>
    @php
    $trip = $this->record;
    $active = $this->activeStop();
    [$badgeText, $badgeColor] = $this->statusBadge($active);
    $progress = $this->progressText();
    @endphp

    {{-- Header ringkas --}}
    <x-filament::section>
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="text-lg font-bold">Mode Eksekusi Trip</div>
                <div class="text-sm text-gray-600">
                    Trip #{{ $trip->id }} • Status: <b>{{ strtoupper($trip->status) }}</b> • {{ $progress }}
                </div>
            </div>

            <x-filament::badge :color="$badgeColor">
                {{ $badgeText }}
            </x-filament::badge>
        </div>
    </x-filament::section>

    @if(! $active)
    <x-filament::section>
        <div class="text-lg font-bold">✅ Semua stop sudah selesai / tidak ada stop aktif.</div>

        <div class="mt-4 flex gap-2">
            <x-filament::button color="success" wire:click="finishTrip">
                Selesaikan Trip
            </x-filament::button>

            <x-filament::button color="gray" tag="a"
                href="{{ \App\Filament\Driver\Resources\DriverTripResource::getUrl('index') }}">
                Kembali ke daftar
            </x-filament::button>
        </div>
    </x-filament::section>
    @else
    {{-- MAP (gudang + toko aktif) --}}
    <x-filament::section>
        <div class="font-bold mb-2">Map (Stop Aktif)</div>
        @include('filament.driver.trip-map-single', ['trip' => $trip, 'stop' => $active])
    </x-filament::section>

    {{-- INFO STOP AKTIF --}}
    <x-filament::section>
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="text-xl font-bold">
                    #{{ $active->sequence }} — {{ $active->store->name }}
                </div>
                <div class="text-sm text-gray-600">
                    {{ $active->store->address }}
                </div>
            </div>

            <div class="flex gap-2">
                <x-filament::badge color="gray">
                    {{ strtoupper($active->status) }}
                </x-filament::badge>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <div class="text-xs text-gray-500">ETA Sistem</div>
                <div class="font-semibold">{{ optional($active->eta_at)->format('H:i') ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Jam Tutup</div>
                <div class="font-semibold">{{ optional($active->close_at)->format('H:i') ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Progress</div>
                <div class="font-semibold">{{ $progress }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Aksi cepat</div>
                <div class="font-semibold">Stop aktif saja</div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="mt-5 flex gap-2 flex-wrap">
            <x-filament::button
                color="gray"
                tag="a"
                :href="$this->gmapsUrl()"
                target="_blank">
                🗺️ Navigasi
            </x-filament::button>

            @if($active->status === 'pending')
            <x-filament::button color="warning" wire:click="markArrived">
                Arrived
            </x-filament::button>
            @endif

            <x-filament::button color="success" wire:click="markDone">
                Done
            </x-filament::button>

            <x-filament::button color="danger" wire:click="markSkip">
                Skip
            </x-filament::button>
        </div>

        {{-- SKIP REASON --}}
        <div class="mt-4">
            {{ $this->form }}
        </div>

    </x-filament::section>

    {{-- Auto refresh ringan (hemat) supaya UI update kalau status berubah --}}
    <div wire:poll.5s="refreshTrip"></div>

    <script>
        document.addEventListener('livewire:init', () => {
            if (window.__driverGeoWatchId !== undefined) {
                return;
            }

            if (!navigator.geolocation) {
                return;
            }

            const componentId = @json($this->getId());
            const getComponent = () => window.Livewire?.find(componentId);

            window.__driverGeoWatchId = navigator.geolocation.watchPosition(
                (pos) => {
                    const now = Date.now();
                    if (window.__driverGeoLastSent && now - window.__driverGeoLastSent < 5000) {
                        return;
                    }
                    window.__driverGeoLastSent = now;
                    const component = getComponent();
                    if (!component) return;
                    component.call('updateDriverLocation', pos.coords.latitude, pos.coords.longitude);
                },
                (err) => {
                    console.debug('Geolocation error', err);
                },
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 10000 },
            );

            document.addEventListener('livewire:navigating', () => {
                if (window.__driverGeoWatchId === undefined) return;
                navigator.geolocation.clearWatch(window.__driverGeoWatchId);
                window.__driverGeoWatchId = undefined;
            }, { once: true });
        });
    </script>
    @endif
</x-filament::page>
