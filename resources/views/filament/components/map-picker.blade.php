@php
$latField = $latField ?? 'lat';
$lngField = $lngField ?? 'lng';
$addressField = $addressField ?? 'address';
$statePrefix = $statePrefix ?? 'data';
$height = $height ?? '360px';
$radius = (int) ($radius ?? 0);
@endphp

@once
{{-- Leaflet CSS --}}
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin="" />

{{-- Leaflet JS --}}
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
@endonce

<div
    wire:ignore
    x-data="mapPicker({
        statePrefix: '{{ $statePrefix }}',
        latField: '{{ $latField }}',
        lngField: '{{ $lngField }}',
        addressField: '{{ $addressField }}',
        radius: {{ $radius }},
        geocodeUrl: '{{ route('admin.api.geocode') }}',
        reverseUrl: '{{ route('admin.api.reverse') }}',
    })"
    class="space-y-4">
    
    {{-- Pencarian & Locate Me --}}
    <div class="flex gap-2">
        <div class="relative flex-1">
            <input
                x-model="query"
                type="text"
                placeholder="Cari toko / building / alamat..."
                class="fi-input w-full block border-none py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500 rounded-lg pr-10"
                @input.debounce.400ms="doSearch()" />
            
            {{-- Hasil Search --}}
            <template x-if="results.length">
                <div class="absolute z-[1000] w-full mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl max-h-60 overflow-y-auto">
                    <template x-for="(r, idx) in results" :key="idx">
                        <button type="button"
                            class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 border-b border-gray-100 dark:border-gray-800 last:border-0"
                            @click="pickResult(r)">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="r.label"></span>
                                <span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500" x-text="r.source"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        <button 
            type="button"
            @click="locateMe()"
            class="p-2 bg-white dark:bg-white/5 ring-1 ring-inset ring-gray-300 dark:ring-white/10 rounded-lg text-gray-500 hover:text-primary-500 transition shadow-sm"
            title="Gunakan Lokasi Saya Saat Ini">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z" />
            </svg>
        </button>
    </div>

    {{-- Paste dari Google Maps --}}
    <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tempel koordinat dari Google Maps (misal: -3.31, 114.59)</label>
        <input
            type="text"
            placeholder="-3.3190, 114.5900"
            @input.debounce.300ms="parsePaste($el.value)"
            class="fi-input block w-full border-none py-1 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-primary-500 sm:text-xs rounded-lg dark:bg-white/5 dark:text-white dark:ring-white/5" />
    </div>

    <div class="border rounded-lg shadow-inner overflow-hidden relative" style="height: {{ $height }}; background:#111;" x-ref="map">
        {{-- Loading Overlay --}}
        <div x-show="loading" class="absolute inset-0 z-[2000] bg-black/20 flex items-center justify-center backdrop-blur-[1px]">
             <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
        </div>
    </div>
</div>

<script>
    if (typeof window.mapPicker === 'undefined') {
        window.mapPicker = function(cfg) {
            return {
                query: '',
                results: [],
                map: null,
                marker: null,
                geofence: null,
                loading: false,

                getCurrentLat() {
                    return this.$wire.get(cfg.statePrefix + '.' + cfg.latField);
                },
                getCurrentLng() {
                    return this.$wire.get(cfg.statePrefix + '.' + cfg.lngField);
                },

                init() {
                    const fallbackLat = window.WAREHOUSE_LAT ?? -3.3190;
                    const fallbackLng = window.WAREHOUSE_LNG ?? 114.5900;

                    const existingLat = this.getCurrentLat();
                    const existingLng = this.getCurrentLng();

                    const startLat = existingLat ?? fallbackLat;
                    const startLng = existingLng ?? fallbackLng;

                    this.map = L.map(this.$refs.map).setView([startLat, startLng], 14);

                    L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                        maxZoom: 20,
                        attribution: 'Google Maps',
                    }).addTo(this.map);

                    this.marker = L.marker([startLat, startLng], {
                        draggable: true
                    }).addTo(this.map);

                    if (cfg.radius > 0) {
                        this.geofence = L.circle([startLat, startLng], {
                            radius: cfg.radius,
                            color: '#3b82f6', // Tailwind primary-500
                            fillColor: '#3b82f6',
                            fillOpacity: 0.1,
                            weight: 1
                        }).addTo(this.map);
                    }

                    this.marker.on('dragend', (e) => {
                        const p = e.target.getLatLng();
                        this.setPoint(p.lat, p.lng, true);
                    });

                    this.map.on('click', (e) => {
                        this.setPoint(e.latlng.lat, e.latlng.lng, true);
                    });

                    setTimeout(() => this.map.invalidateSize(), 200);
                },

                async doSearch() {
                    if (!this.query || this.query.length < 3) {
                        this.results = [];
                        return;
                    }

                    this.loading = true;
                    try {
                        const url = new URL(cfg.geocodeUrl, window.location.origin);
                        url.searchParams.set('q', this.query);

                        const res = await fetch(url.toString(), {
                            credentials: 'same-origin'
                        });
                        const json = await res.json();
                        this.results = json?.data ?? [];
                    } finally {
                        this.loading = false;
                    }
                },

                async pickResult(r) {
                    this.results = [];
                    this.query = r.label;
                    await this.setPoint(r.lat, r.lng, false);
                    if (cfg.addressField) {
                        this.$wire.set(cfg.statePrefix + '.' + cfg.addressField, r.label);
                    }
                },

                locateMe() {
                    if (!navigator.geolocation) return;
                    this.loading = true;
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.setPoint(pos.coords.latitude, pos.coords.longitude, true);
                            this.loading = false;
                        },
                        () => { this.loading = false; }
                    );
                },

                parsePaste(val) {
                    if (!val) return;
                    // regex untuk handle "-3.31, 114.59" atau "-3.31 114.59"
                    const match = val.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/) || val.match(/(-?\d+\.\d+)\s+(-?\d+\.\d+)/);
                    if (match) {
                        this.setPoint(match[1], match[2], true);
                    }
                },

                async setPoint(lat, lng, doReverse) {
                    lat = Number(lat);
                    lng = Number(lng);

                    if (this.marker) this.marker.setLatLng([lat, lng]);
                    if (this.geofence) this.geofence.setLatLng([lat, lng]);
                    if (this.map) this.map.setView([lat, lng], 17);

                    this.$wire.set(cfg.statePrefix + '.' + cfg.latField, lat);
                    this.$wire.set(cfg.statePrefix + '.' + cfg.lngField, lng);

                    if (doReverse && cfg.addressField) {
                        const url = new URL(cfg.reverseUrl, window.location.origin);
                        url.searchParams.set('lat', lat);
                        url.searchParams.set('lng', lng);

                        const res = await fetch(url.toString(), { credentials: 'same-origin' });
                        const json = await res.json();
                        const label = json?.data?.label;
                        if (label) this.$wire.set(cfg.statePrefix + '.' + cfg.addressField, label);
                    }
                },
            }
        }
    }
</script>

 