<x-filament::page>
    @php
    $trip = $this->record;
    $active = $this->activeStop();
    [$badgeText, $badgeColor] = $this->statusBadge($active);
    $progress = $this->progressText();
    
    $total = $this->totalStops();
    $done = $this->doneStops();
    $percent = $total > 0 ? ($done / $total) * 100 : 0;
    @endphp

    {{-- Simple Header --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 mb-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold">Trip #{{ $trip->id }}</h2>
                <p class="text-xs text-gray-500 uppercase font-semibold">Driver Delivery Mode</p>
            </div>
            <div class="text-right">
                <x-filament::badge :color="$badgeColor" size="sm">
                    {{ $badgeText }}
                </x-filament::badge>
                <div id="connection-indicator" class="mt-1 flex items-center justify-end gap-1 text-[10px] text-success-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-success-500 animate-pulse"></span>
                    Online
                </div>
            </div>
        </div>

        {{-- Simple Progress --}}
        <div class="space-y-1">
            <div class="flex justify-between text-xs font-medium text-gray-600">
                <span>{{ $progress }}</span>
                <span>{{ round($percent) }}%</span>
            </div>
            <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                <div class="h-full bg-primary-600 transition-all duration-500" style="width: {{ $percent }}%"></div>
            </div>
        </div>
    </div>

    @if(! $active)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-8 text-center shadow-sm">
        <div class="mb-4 text-success-600 flex justify-center">
            <x-heroicon-o-check-circle class="w-16 h-16" />
        </div>
        <h3 class="text-xl font-bold mb-2">Trip Selesai!</h3>
        <p class="text-gray-500 mb-6">Semua pengiriman telah berhasil diselesaikan.</p>
        
        <div class="flex flex-col gap-2">
            <x-filament::button color="success" wire:click="finishTrip" size="lg">
                Selesaikan Trip Ini
            </x-filament::button>
            <x-filament::button color="gray" tag="a" variant="link"
                href="{{ \App\Filament\Driver\Resources\DriverTripResource::getUrl('index') }}">
                Kembali ke Daftar
            </x-filament::button>
        </div>
    </div>
    @else
    <div class="space-y-6">
        {{-- Map --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            @include('filament.driver.trip-map-single', ['trip' => $trip, 'stop' => $active])
        </div>

        {{-- Active Stop --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-primary-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold">
                    {{ $active->sequence }}
                </div>
                <div>
                    <h3 class="font-bold text-lg leading-tight">{{ $active->store->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $active->store->address }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <span class="block text-[10px] text-gray-400 uppercase font-bold">ETA</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ optional($active->eta_at)->format('H:i') ?? '--:--' }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <span class="block text-[10px] text-gray-400 uppercase font-bold">Jam Tutup</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ optional($active->close_at)->format('H:i') ?? '--:--' }}</span>
                </div>
            </div>

            {{-- Primary Actions --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 mb-4">
                <x-filament::button
                    size="lg"
                    color="gray"
                    tag="a"
                    :href="$this->gmapsUrl()"
                    target="_blank"
                    icon="heroicon-m-map"
                    class="w-full">
                    Navigasi
                </x-filament::button>

                @if($active->status === 'pending')
                    <x-filament::button size="lg" color="warning" wire:click="markArrived" icon="heroicon-m-check-circle" class="w-full">
                        Arrived
                    </x-filament::button>
                @else
                    <div class="flex items-center justify-center p-2 bg-success-50 dark:bg-success-950 text-success-700 dark:text-success-400 rounded-lg border border-success-200 dark:border-success-800 font-bold text-sm">
                        <x-heroicon-m-check-circle class="w-5 h-5 mr-2" />
                        Sudah di Lokasi
                    </div>
                @endif
            </div>

            {{-- Secondary Actions --}}
            <x-filament::button variant="link" color="danger" wire:click="markRejected" class="w-full text-xs uppercase font-bold">
                Gagal / Reject
            </x-filament::button>
        </div>

        {{-- Laporan Kendala --}}
        <x-filament::section>
            <x-slot name="heading">
                Laporan Kendala (Opsional)
            </x-slot>
            
            {{ $this->form }}
        </x-filament::section>
    </div>
    @endif

    {{-- Refresh --}}
    <div wire:poll.15s="refreshTrip"></div>

    @vite('resources/js/capacitor-location.js')

    <script type="module">
        // Import Capacitor location tracker if available
        const isNative = typeof window.Capacitor !== 'undefined' && window.Capacitor.getPlatform() !== 'web';

        document.addEventListener('livewire:init', async () => {
            if (window.__driverGeoWatchId !== undefined) {
                return;
            }

            const componentId = @json($this->getId());
            const getComponent = () => window.Livewire?.find(componentId);

            // Check if running in Capacitor
            if (isNative && window.CapacitorLocationTracker) {
                console.log('Using Capacitor Native Location');
                
                const tracker = new window.CapacitorLocationTracker();
                
                try {
                    await tracker.startTracking((position) => {
                        const now = Date.now();
                        if (window.__driverGeoLastSent && now - window.__driverGeoLastSent < 5000) {
                            return;
                        }
                        window.__driverGeoLastSent = now;
                        
                        const component = getComponent();
                        if (!component) return;
                        
                        component.call('updateDriverLocation', position.latitude, position.longitude);
                        console.log('📍 Native location update', position.latitude, position.longitude);
                    });
                    
                    window.__driverGeoTracker = tracker;
                    
                    document.addEventListener('livewire:navigating', () => {
                        if (window.__driverGeoTracker) {
                            window.__driverGeoTracker.stopTracking();
                            window.__driverGeoTracker = null;
                        }
                    }, { once: true });
                    
                } catch (error) {
                    console.error('Failed to start native location tracking:', error);
                    alert('Gagal memulai GPS. Pastikan izin lokasi sudah diaktifkan.');
                }
                
            } else {
                // Fallback to web geolocation
                console.log('Using Web Geolocation API');
                
                if (!navigator.geolocation) {
                    console.error('Geolocation not supported');
                    return;
                }

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
            }
        });

        // Connectivity Monitoring
        (function() {
            const indicator = document.getElementById('connection-indicator');
            if (!indicator) return;

            function updateStatus() {
                if (navigator.onLine) {
                    indicator.className = 'flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-success-500/10 text-success-600';
                    indicator.innerHTML = '<span class="w-2 h-2 rounded-full bg-success-500 animate-pulse"></span> Online';
                } else {
                    indicator.className = 'flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-danger-500/10 text-danger-600';
                    indicator.innerHTML = '<span class="w-2 h-2 rounded-full bg-danger-500"></span> Offline';
                }
            }

            window.addEventListener('online', updateStatus);
            window.addEventListener('offline', updateStatus);
            updateStatus();
        })();
    </script>
</x-filament::page>
