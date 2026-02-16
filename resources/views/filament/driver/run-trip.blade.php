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

    {{-- Enhanced Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-primary-500 to-primary-700 dark:from-primary-600 dark:to-primary-900 rounded-2xl p-6 mb-6 shadow-xl">
        <!-- Decorative Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <x-heroicon-o-truck class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Trip #{{ $trip->id }}</h2>
                        <p class="text-xs text-primary-100 uppercase font-semibold tracking-wide">Driver Delivery Mode</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                        <span class="text-sm font-bold text-white">{{ $badgeText }}</span>
                    </div>
                    <div id="connection-indicator" class="mt-2 flex items-center justify-end gap-1.5 text-xs text-white/80">
                        <span class="w-2 h-2 rounded-full bg-success-400 animate-pulse shadow-lg shadow-success-500/50"></span>
                        Online
                    </div>
                </div>
            </div>

            {{-- Enhanced Progress Bar --}}
            <div class="space-y-2">
                <div class="flex justify-between text-sm font-semibold text-white/90">
                    <span>{{ $progress }}</span>
                    <span>{{ round($percent) }}%</span>
                </div>
                <div class="relative w-full h-3 bg-white/20 backdrop-blur-sm rounded-full overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-success-400 to-success-500 transition-all duration-500 rounded-full shadow-lg" style="width: {{ $percent }}%"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-transparent to-white/20"></div>
                </div>
            </div>
        </div>
    </div>

    @if(! $active)
    <div class="relative overflow-hidden bg-gradient-to-br from-success-50 to-success-100 dark:from-success-950 dark:to-gray-900 border-2 border-success-200 dark:border-success-800 rounded-2xl p-8 text-center shadow-xl">
        <div class="absolute top-0 right-0 w-64 h-64 bg-success-200/30 dark:bg-success-900/20 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-success-300/20 dark:bg-success-800/10 rounded-full -ml-24 -mb-24"></div>
        
        <div class="relative z-10">
            <div class="mb-6 flex justify-center">
                <div class="p-4 bg-success-100 dark:bg-success-900/50 rounded-full">
                    <x-heroicon-o-check-circle class="w-20 h-20 text-success-600 dark:text-success-400" />
                </div>
            </div>
            <h3 class="text-3xl font-bold mb-3 text-gray-900 dark:text-white">Trip Selesai!</h3>
            <p class="text-gray-600 dark:text-gray-300 mb-8 text-lg">Semua pengiriman telah berhasil diselesaikan.</p>
            
            <div class="flex flex-col gap-3 max-w-sm mx-auto">
                <x-filament::button color="success" wire:click="finishTrip" size="xl" class="shadow-lg hover:shadow-xl transition-shadow">
                    <x-heroicon-m-check class="w-5 h-5 mr-2" />
                    Selesaikan Trip Ini
                </x-filament::button>
                <x-filament::button color="gray" tag="a" variant="outlined"
                    href="{{ \App\Filament\Driver\Resources\DriverTripResource::getUrl('index') }}">
                    <x-heroicon-m-arrow-left class="w-4 h-4 mr-2" />
                    Kembali ke Daftar
                </x-filament::button>
            </div>
        </div>
    </div>
    @else
    <div class="space-y-6">
        {{-- Map --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            @include('filament.driver.trip-map-single', ['trip' => $trip, 'stop' => $active])
        </div>

        {{-- Enhanced Active Stop Card --}}
        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-xl">
            <div class="flex items-start gap-4 mb-5">
                <div class="flex-shrink-0 bg-gradient-to-br from-primary-500 to-primary-600 text-white w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg shadow-lg">
                    {{ $active->sequence }}
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-xl leading-tight text-gray-900 dark:text-white mb-1">{{ $active->store->name }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                        <x-heroicon-m-map-pin class="w-4 h-4" />
                        {{ $active->store->address }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900 rounded-xl border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-m-clock class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <span class="block text-xs text-blue-600 dark:text-blue-400 uppercase font-bold">ETA</span>
                    </div>
                    <span class="font-bold text-lg text-blue-900 dark:text-blue-100">{{ optional($active->eta_at)->format('H:i') ?? '--:--' }}</span>
                </div>
                <div class="p-4 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950 dark:to-amber-900 rounded-xl border border-amber-200 dark:border-amber-800">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-m-clock class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        <span class="block text-xs text-amber-600 dark:text-amber-400 uppercase font-bold">Jam Tutup</span>
                    </div>
                    <span class="font-bold text-lg text-amber-900 dark:text-amber-100">{{ optional($active->close_at)->format('H:i') ?? '--:--' }}</span>
                </div>
            </div>

            {{-- Primary Actions --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 mb-4">
                <x-filament::button
                    size="xl"
                    color="gray"
                    tag="a"
                    :href="$this->gmapsUrl()"
                    target="_blank"
                    icon="heroicon-m-map"
                    class="w-full shadow-lg hover:shadow-xl transition-shadow">
                    Navigasi
                </x-filament::button>

                @if($active->status === 'pending')
                    <x-filament::button size="xl" color="warning" wire:click="markArrived" icon="heroicon-m-check-circle" class="w-full shadow-lg hover:shadow-xl transition-shadow">
                        Arrived
                    </x-filament::button>
                @else
                    <div class="flex items-center justify-center p-4 bg-gradient-to-r from-success-50 to-success-100 dark:from-success-950 dark:to-success-900 text-success-700 dark:text-success-300 rounded-xl border-2 border-success-200 dark:border-success-800 font-bold text-sm shadow-inner">
                        <x-heroicon-m-check-circle class="w-5 h-5 mr-2" />
                        Sudah di Lokasi
                    </div>
                @endif
            </div>

            {{-- Secondary Actions --}}
            <x-filament::button variant="outlined" color="danger" wire:click="markRejected" class="w-full text-sm uppercase font-bold">
                <x-heroicon-m-x-circle class="w-4 h-4 mr-2" />
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
