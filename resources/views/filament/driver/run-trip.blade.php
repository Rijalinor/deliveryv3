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
    <div class="relative overflow-hidden bg-white dark:bg-gray-900 border-4 border-success-500 rounded-3xl p-10 text-center shadow-2xl">
        <div class="relative z-10">
            <div class="mb-8 flex justify-center">
                <div class="p-6 bg-success-100 dark:bg-success-900/50 rounded-full">
                    <x-heroicon-o-check-circle class="w-24 h-24 text-success-600 dark:text-success-400" />
                </div>
            </div>
            <h3 class="text-4xl font-extrabold mb-4 text-gray-900 dark:text-white">SEMUA SELESAI!</h3>
            <p class="text-xl text-gray-600 dark:text-white mb-6 font-bold">Hebat! Semua pesanan sudah diantar.</p>

            {{-- Fuel Estimation Summary --}}
            <div class="mb-10 p-6 bg-slate-50 dark:bg-slate-800 rounded-3xl border-2 border-slate-200 dark:border-slate-700 max-w-md mx-auto">
                <div class="flex items-center justify-center gap-2 mb-2 text-slate-500 uppercase text-xs font-black tracking-widest">
                    <x-heroicon-m-bolt class="w-4 h-4" />
                    <span>Perkiraan Operasional</span>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white mb-1">
                    Rp {{ number_format($trip->estimated_fuel_cost, 0, ',', '.') }}
                </div>
                <div class="text-sm font-bold text-slate-500">
                    Estimasi BBM (~{{ round($trip->total_distance_m / 1000, 1) }} KM)
                </div>
                <div class="mt-4 text-[10px] text-slate-400 italic">
                    *Nilai perkiraan, kondisi di lapangan mungkin berbeda.
                </div>
            </div>
            
            <div class="flex flex-col gap-4 max-w-md mx-auto">
                <x-filament::button color="success" wire:click="finishTrip" size="xl" class="py-6 text-2xl font-black uppercase tracking-widest shadow-2xl hover:scale-105 transition-transform">
                    SELESAIKAN TRIP
                </x-filament::button>
                <x-filament::button color="gray" tag="a" variant="outlined" size="xl"
                    href="{{ \App\Filament\Driver\Resources\DriverTripResource::getUrl('index') }}" class="py-4 text-xl font-bold">
                    KEMBALI KE DAFTAR
                </x-filament::button>
            </div>
        </div>
    </div>
@else
    @php
        $cluster = $this->getNearbyStops();
        $isCluster = $cluster->count() > 1;
    @endphp

    <div class="space-y-8">
        {{-- VOICE ASSISTANT CONTROLS --}}
        <div class="flex justify-end gap-2 mb-4" x-data="{ 
            muted: localStorage.getItem('voice_muted') === 'true'
        }">
            <button 
                @click="muted = !muted; localStorage.setItem('voice_muted', muted); $dispatch('toggle-voice', { muted: muted })"
                class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-white/20 dark:border-gray-700/30 shadow-lg hover:bg-white dark:hover:bg-gray-800 transition-all font-bold text-xs"
                :class="muted ? 'opacity-50' : 'opacity-100 border-primary-500/50'"
            >
                <span x-show="!muted" class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-success-500 animate-pulse"></span> 🔊 SUARA ON</span>
                <span x-show="muted" class="flex items-center gap-1.5">🔇 SUARA OFF</span>
            </button>

            <button 
                @click="$dispatch('test-voice')"
                class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-white/20 dark:border-gray-700/30 shadow-lg hover:bg-white dark:hover:bg-gray-800 transition-all font-bold text-xs"
            >
                <span>📢 TEST</span>
            </button>

        </div>

        <script>
            document.addEventListener('livewire:init', () => {
                let isMuted = localStorage.getItem('voice_muted') === 'true';
                let idVoice = null;

                const loadVoices = () => {
                    const voices = window.speechSynthesis.getVoices();
                    idVoice = voices.find(v => 
                        v.lang.toLowerCase().includes('id') || 
                        v.name.toLowerCase().includes('indonesia')
                    );
                    if (idVoice) console.log('✅ ID Voice found:', idVoice.name);
                };

                window.speechSynthesis.onvoiceschanged = loadVoices;
                loadVoices();

                document.addEventListener('toggle-voice', (e) => {
                    isMuted = e.detail.muted;
                });

                document.addEventListener('test-voice', () => {
                    speak('Percobaan suara asisten Delivery v3 berhasil.');
                });

                const speak = async (message) => {
                    if (isMuted) return;

                    // 1. Cek Native Capacitor Plugin (Prioritas untuk APK)
                    const isNative = typeof window.Capacitor !== 'undefined' && window.Capacitor.getPlatform() !== 'web';
                    if (isNative) {
                        try {
                            const { TextToSpeech } = window.Capacitor.Plugins;
                            if (TextToSpeech) {
                                await TextToSpeech.speak({
                                    text: message,
                                    lang: 'id-ID',
                                    rate: 0.9, // Slower is more natural
                                    pitch: 1.0,
                                    volume: 1.0,
                                    category: 'ambient',
                                });
                                console.log('🗣️ Native APK Speaking:', message);
                                return; // Keluar jika berhasil native
                            }
                        } catch (err) {
                            console.error('❌ Native TTS failed, falling back to Web:', err);
                        }
                    }

                    // 2. Fallback ke Web Speech API (Paling stabil di Browser Chrome)
                    if (!window.speechSynthesis) {
                        console.error('❌ SpeechSynthesis not supported!');
                        return;
                    }

                    // Force reload if not found
                    if (!idVoice) loadVoices();

                    const utterance = new SpeechSynthesisUtterance(message);
                    utterance.lang = 'id-ID'; 
                    utterance.rate = 0.9; // Slower is more natural
                    utterance.pitch = 1.0;
                    
                    if (idVoice) {
                        utterance.voice = idVoice;
                    } else {
                        console.warn('⚠️ ID Voice not found, trying default browser voice.');
                    }

                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(utterance);
                    console.log('🗣️ Web Speaking:', message);
                };

                Livewire.on('voice-alert', (event) => {
                    speak(event.message);
                });
            });
        </script>

        {{-- Map --}}
        <div id="map-container" class="bg-white dark:bg-gray-900 border-4 border-primary-500 rounded-3xl overflow-hidden shadow-2xl">
            @include('filament.driver.trip-map-single', ['trip' => $trip, 'stop' => $active])
        </div>

        @if($isCluster)
            {{-- MARKET MODE / CLUSTER VIEW --}}
            <div class="bg-white dark:bg-gray-800 border-4 border-amber-400 rounded-3xl p-6 shadow-2xl overflow-hidden">
                <div class="flex items-center gap-4 mb-6 pb-4 border-b-2 border-amber-100 dark:border-amber-900/50">
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl text-amber-700 dark:text-amber-400">
                        <x-heroicon-m-building-storefront class="w-8 h-8" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase leading-none">MODE PASAR / CLUSTER</h3>
                        <p class="text-sm font-bold text-amber-600 uppercase tracking-widest mt-1">{{ $cluster->count() }} Toko Berdekatan</p>
                    </div>
                </div>

                <div class="space-y-4 mb-8">
                    @foreach($cluster as $s)
                        @php
                            $isActiveItem = $s->id === $active->id;
                            $itemStatus = $s->status;
                        @endphp
                        <div class="relative p-5 rounded-2xl border-2 {{ $isActiveItem ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30' : 'border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50' }} transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg {{ $isActiveItem ? 'bg-primary-600' : 'bg-slate-400' }} text-white flex items-center justify-center font-black text-xl">
                                    {{ $s->sequence }}
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-lg {{ $isActiveItem ? 'text-primary-900 dark:text-white' : 'text-slate-600 dark:text-slate-400' }} uppercase">{{ $s->store->name }}</h4>
                                    <p class="text-xs font-semibold text-slate-500 line-clamp-1 truncate">{{ $s->store->address }}</p>
                                </div>
                                <div class="text-right">
                                    @if($itemStatus === 'arrived')
                                        <span class="px-3 py-1 bg-success-500 text-white text-[10px] font-black rounded-full uppercase">Arrived</span>
                                    @else
                                        <span class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-500 text-[10px] font-black rounded-full uppercase">{{ $itemStatus }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($isActiveItem)
                                <div class="absolute -right-2 top-1/2 -translate-y-1/2">
                                    <div class="w-4 h-8 bg-primary-500 rounded-l-full"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Cluster Actions (Fokus ke Active Stop dalam cluster) --}}
                <div class="space-y-4">
                    <div class="p-4 bg-primary-600 text-white rounded-2xl font-black text-center text-xl shadow-lg uppercase tracking-wider">
                        Sedang Diproses: {{ $active->store->name }}
                    </div>

                    @if($trip->status === 'planned')
                        @include('filament.driver.partials.start-trip-button')
                    @else
                        <div class="grid grid-cols-1 gap-4">
                            @if($active->status === 'pending')
                                <x-filament::button size="xl" color="success" wire:click="markArrived" icon="heroicon-m-check-circle" class="w-full py-6 text-2xl font-black uppercase tracking-wider shadow-xl rounded-2xl border-b-4 border-success-700">
                                    SAYA SUDAH SAMPAI
                                </x-filament::button>
                            @else
                                <x-filament::button size="xl" color="success" wire:click="markDone" icon="heroicon-m-check-badge" class="w-full py-6 text-2xl font-black uppercase tracking-wider shadow-xl rounded-2xl border-b-4 border-success-700">
                                    SELESAI ANTAR
                                </x-filament::button>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-filament::button variant="outlined" color="warning" wire:click="postponeStop" class="py-4 text-sm font-black uppercase tracking-tighter border-2 rounded-xl">
                                <x-heroicon-m-arrow-path class="w-5 h-5 mr-1" />
                                TUNDA
                            </x-filament::button>

                            <x-filament::button variant="outlined" color="danger" wire:click="markRejected" class="py-4 text-sm font-black uppercase tracking-tighter border-2 rounded-xl">
                                <x-heroicon-m-x-circle class="w-5 h-5 mr-1" />
                                REJECT
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- STANDARD MODE (SINGLE STOP) --}}
            <div class="bg-white dark:bg-gray-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-8 shadow-2xl">
                {{-- Stop Number & Store Name --}}
                <div class="flex items-center gap-6 mb-8 border-b-4 border-slate-100 dark:border-slate-700 pb-6">
                    <div class="flex-shrink-0 bg-primary-600 text-white w-20 h-20 rounded-2xl flex items-center justify-center font-black text-4xl shadow-xl">
                        {{ $active->sequence }}
                    </div>
                    <div class="flex-1">
                        <h3 class="font-black text-4xl leading-tight text-gray-900 dark:text-white uppercase mb-2">{{ $active->store->name }}</h3>
                        <div class="inline-flex items-center gap-2 px-6 py-2 bg-slate-100 dark:bg-slate-700 rounded-full">
                            <x-heroicon-m-map-pin class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            <span class="text-xl font-bold text-gray-700 dark:text-white tracking-tight">{{ $active->store->address }}</span>
                        </div>
                    </div>
                </div>

                {{-- High Contrast Info Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/50 rounded-xl border border-blue-200 dark:border-blue-700 flex items-center gap-3">
                        <x-heroicon-m-clock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        <div>
                            <span class="block text-[10px] font-bold text-blue-600 dark:text-blue-300 uppercase tracking-wider">ETA</span>
                            <span class="font-bold text-lg text-blue-900 dark:text-white">{{ optional($active->eta_at)->format('H:i') ?? '--:--' }}</span>
                        </div>
                    </div>
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/50 rounded-xl border border-amber-200 dark:border-amber-700 flex items-center gap-3">
                        <x-heroicon-m-exclamation-triangle class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                        <div>
                            <span class="block text-[10px] font-bold text-amber-600 dark:text-amber-300 uppercase tracking-wider">Toko Tutup</span>
                            <span class="font-bold text-xl text-amber-900 dark:text-white">{{ optional($active->store->close_time)->format('H:i') ?? '--:--' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Main Giant Actions --}}
                <div class="space-y-4">
                    {{-- Primary Action Row --}}
                    <div class="grid grid-cols-1 gap-4">
                        <x-filament::button
                            size="xl"
                            color="primary"
                            tag="a"
                            :href="$this->gmapsUrl()"
                            target="_blank"
                            icon="heroicon-m-map"
                            class="w-full py-6 text-2xl font-black uppercase tracking-wider shadow-xl rounded-2xl border-b-4 border-primary-700">
                            BUKA PETA
                        </x-filament::button>

                        @if($trip->status === 'planned')
                            @include('filament.driver.partials.start-trip-button')
                        @else
                            @if($active->status === 'pending')
                                <x-filament::button size="xl" color="success" wire:click="markArrived" icon="heroicon-m-check-circle" class="w-full py-6 text-2xl font-black uppercase tracking-wider shadow-xl rounded-2xl border-b-4 border-success-700">
                                    SAYA SUDAH SAMPAI
                                </x-filament::button>
                            @else
                                <x-filament::button size="xl" color="success" wire:click="markDone" icon="heroicon-m-check-badge" class="w-full py-6 text-2xl font-black uppercase tracking-wider shadow-xl rounded-2xl border-b-4 border-success-700">
                                    PENGIRIMAN SELESAI
                                </x-filament::button>
                            @endif
                        @endif
                    </div>

                    @if($trip->status !== 'planned')
                    {{-- Secondary Action Row (Grouped) --}}
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <x-filament::button variant="outlined" color="warning" wire:click="postponeStop" class="py-4 text-sm font-black uppercase tracking-tighter border-2 rounded-xl">
                            <x-heroicon-m-arrow-path class="w-5 h-5 mr-1" />
                            TUNDA
                        </x-filament::button>

                        <x-filament::button variant="outlined" color="danger" wire:click="markRejected" class="py-4 text-sm font-black uppercase tracking-tighter border-2 rounded-xl">
                            <x-heroicon-m-x-circle class="w-5 h-5 mr-1" />
                            REJECT
                        </x-filament::button>
                    </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Laporan Kendala - Extra Large --}}
        <x-filament::section collapsible class="border-4 border-slate-200 rounded-3xl overflow-hidden shadow-xl">
            <x-slot name="heading">
                <span class="text-2xl font-black uppercase text-slate-700 dark:text-slate-200">Klik Jika Ada Masalah</span>
            </x-slot>
            
            <div class="p-2">
                {{ $this->form }}
            </div>
        </x-filament::section>
    </div>
    @endif

    {{-- Refresh --}}
    <div wire:poll.30s="refreshTrip"></div>

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
                        if (window.__driverGeoLastSent && now - window.__driverGeoLastSent < 10000) {
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
                        if (window.__driverGeoLastSent && now - window.__driverGeoLastSent < 10000) {
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
    {{-- READINESS CHECK OVERLAY AT ROOT --}}
</x-filament::page>
