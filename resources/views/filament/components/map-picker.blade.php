@php
// Pastikan field ini ada di form:
// Hidden::make('lat'), Hidden::make('lng'), Textarea::make('address')
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

<script>
    window.mapPicker = function(cfg) {
        return {
            query: '',
            results: [],
            map: null,
            marker: null,

            // ambil state awal dari form (kalau edit record)
            getCurrentLat() {
                return this.$wire.get(cfg.statePrefix + '.lat');
            },
            getCurrentLng() {
                return this.$wire.get(cfg.statePrefix + '.lng');
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

                this.marker.on('dragend', (e) => {
                    const p = e.target.getLatLng();
                    this.setPoint(p.lat, p.lng, true);
                });

                this.map.on('click', (e) => {
                    this.setPoint(e.latlng.lat, e.latlng.lng, true);
                });

                // penting biar map tidak blank karena render Filament
                setTimeout(() => this.map.invalidateSize(), 200);
            },

            async doSearch() {
                if (!this.query || this.query.length < 3) {
                    this.results = [];
                    return;
                }

                const url = new URL(cfg.geocodeUrl, window.location.origin);
                url.searchParams.set('q', this.query);

                const res = await fetch(url.toString(), {
                    credentials: 'same-origin'
                });
                const json = await res.json();
                this.results = json?.data ?? [];
            },

            async pickResult(r) {
                this.results = [];
                this.query = r.label;

                await this.setPoint(r.lat, r.lng, false);

                // isi address di form
                this.$wire.set(cfg.statePrefix + '.address', r.label);
            },

            async setPoint(lat, lng, doReverse) {
                lat = Number(lat);
                lng = Number(lng);

                // update marker
                if (this.marker) this.marker.setLatLng([lat, lng]);
                if (this.map) this.map.setView([lat, lng], 15);

                // ✅ update state Filament form (ini yang membuat tersimpan)
                this.$wire.set(cfg.statePrefix + '.lat', lat);
                this.$wire.set(cfg.statePrefix + '.lng', lng);

                // reverse geocode untuk address
                if (doReverse) {
                    const url = new URL(cfg.reverseUrl, window.location.origin);
                    url.searchParams.set('lat', lat);
                    url.searchParams.set('lng', lng);

                    const res = await fetch(url.toString(), {
                        credentials: 'same-origin'
                    });
                    const json = await res.json();
                    const label = json?.data?.label;
                    if (label) this.$wire.set(cfg.statePrefix + '.address', label);
                }
            },
        }
    }
</script>
@endonce

{{-- ✅ wire:ignore supaya map tidak hilang saat state berubah --}}
<div
    wire:ignore
    x-data="mapPicker({
        statePrefix: 'data',
        geocodeUrl: '{{ route('admin.api.geocode') }}',
        reverseUrl: '{{ route('admin.api.reverse') }}',
    })"
    class="space-y-3">
    <input
        x-model="query"
        type="text"
        placeholder="Cari alamat / nama tempat (ORS)..."
        class="fi-input w-full"
        @input.debounce.400ms="doSearch()" />

    <template x-if="results.length">
        <div class="border rounded p-2 space-y-1 bg-white dark:bg-gray-900 dark:border-gray-700">
            <template x-for="(r, idx) in results" :key="idx">
                <button type="button"
                    class="w-full text-left px-2 py-1 rounded
               hover:bg-gray-100 dark:hover:bg-gray-800
               text-gray-900 dark:text-gray-100"
                    @click="pickResult(r)">

            </template>
        </div>
    </template>

    <div class="border rounded" style="height: 360px; background:#111;" x-ref="map"></div>

 