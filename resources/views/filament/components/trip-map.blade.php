{{-- resources/views/filament/components/trip-map.blade.php --}}

@php
/** @var \App\Models\Trip|null $trip */

// Filament kadang ngasih state sebagai Closure, jadi ambil lewat $getState()
$trip = null;

if (isset($getState) && is_callable($getState)) {
$trip = $getState();
} else {
$trip = is_callable($state ?? null) ? $state() : ($state ?? null);
}

if (! $trip) {
echo '<div class="p-4 rounded-lg border border-danger-600 text-danger-600">
    trip-map.blade.php: Trip tidak kebaca. Pastikan ViewEntry mengirim record.
</div>';
return;
}

$warehouse = [
'lat' => (float) ($trip->start_lat ?? config('delivery.warehouse_lat', 0)),
'lng' => (float) ($trip->start_lng ?? config('delivery.warehouse_lng', 0)),
];

$stops = $trip->stops()
->with('store:id,name,lat,lng,address')
->orderBy('sequence')
->get()
->map(fn ($s) => [
'name'    => $s->store?->name,
'address' => $s->store?->address,
'lat'     => (float) ($s->store?->lat ?? 0),
'lng'     => (float) ($s->store?->lng ?? 0),
'status'  => $s->status,
'seq'     => $s->sequence,
])
->values()
->all();

$geo = $trip->route_geojson ? json_decode($trip->route_geojson, true) : null;

$driver = null;

if ($trip->current_lat && $trip->current_lng) {
    $driver = [
        'lat'  => (float) $trip->current_lat,
        'lng'  => (float) $trip->current_lng,
        'time' => $trip->updated_at->format('H:i:s'),
    ];
} else {
    $latestLocation = \App\Models\DriverLocation::where('trip_id', $trip->id)
        ->latest()
        ->first();

    $driver = $latestLocation ? [
        'lat'  => (float) $latestLocation->lat,
        'lng'  => (float) $latestLocation->lng,
        'time' => $latestLocation->created_at->format('H:i:s'),
    ] : null;
}

// Check if there's any GPS history for replay
$hasLocationHistory = \App\Models\DriverLocation::where('trip_id', $trip->id)->exists();
$locationHistoryUrl = route('api.trip.location-history', $trip->id);

$mapId = 'trip-map-' . $trip->id;
@endphp


<div class="rounded-xl border border-gray-800 overflow-hidden">
    <div id="{{ $mapId }}" style="height: 520px; width: 100%;"></div>
</div>

{{-- Replay Panel (only shown if GPS history exists) --}}
@if($hasLocationHistory)
<div id="replay-panel-{{ $trip->id }}" class="mt-3 p-4 rounded-xl border border-gray-700 bg-gray-900">
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                <span class="text-sm font-semibold text-white">Replay Perjalanan GPS</span>
                <span id="replay-status-{{ $trip->id }}" class="text-xs text-gray-400 ml-1"></span>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-400">Kecepatan:</label>
                <select id="replay-speed-{{ $trip->id }}" class="text-xs bg-gray-800 border border-gray-600 text-white rounded px-2 py-1">
                    <option value="1">1x</option>
                    <option value="2">2x</option>
                    <option value="5" selected>5x</option>
                    <option value="10">10x</option>
                    <option value="20">20x</option>
                </select>
            </div>
        </div>

        <input
            type="range"
            id="replay-slider-{{ $trip->id }}"
            min="0" max="100" value="0" step="1"
            class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-orange-500"
            style="disabled"
        />

        <div class="flex items-center justify-between">
            <div class="flex gap-2">
                <button
                    id="replay-play-{{ $trip->id }}"
                    onclick="replayControl_{{ $trip->id }}('play')"
                    class="flex items-center gap-1 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Play
                </button>
                <button
                    id="replay-pause-{{ $trip->id }}"
                    onclick="replayControl_{{ $trip->id }}('pause')"
                    class="hidden flex items-center gap-1 px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded-lg transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                    </svg>
                    Pause
                </button>
                <button
                    onclick="replayControl_{{ $trip->id }}('reset')"
                    class="flex items-center gap-1 px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white text-xs font-medium rounded-lg transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    Reset
                </button>
            </div>
            <span id="replay-time-{{ $trip->id }}" class="text-xs text-gray-400">-</span>
        </div>
    </div>
</div>
@endif

@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
@endonce

@push('scripts')
<script>
(function () {
    const mapId = @json($mapId);
    const tripId = @json($trip->id);
    const el = document.getElementById(mapId);
    if (!el) return;

    function initTripMap() {
        // destroy dulu biar gak "already initialized"
        if (el._leaflet_map) {
            el._leaflet_map.remove();
            el._leaflet_map = null;
        }

        const warehouse = @json($warehouse);
        const stops = @json($stops);
        const geojson = @json($geo);

        if (!warehouse.lat || !warehouse.lng) {
            el.innerHTML = '<div style="padding:12px">Koordinat gudang belum ada.</div>';
            return;
        }

        const map = L.map(mapId).setView([warehouse.lat, warehouse.lng], 13);
        el._leaflet_map = map;

        L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: 'Google Maps',
        }).addTo(map);

        const bounds = L.latLngBounds([]);

        // Marker Gudang
        L.marker([warehouse.lat, warehouse.lng]).addTo(map).bindPopup('<b>Gudang</b>');
        bounds.extend([warehouse.lat, warehouse.lng]);

        // Icons (warna)
        const iconBlue = new L.Icon({
            iconUrl: '/leaflet/marker-icon-blue.png',
            shadowUrl: '/leaflet/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41],
        });
        const iconGreen = new L.Icon({
            iconUrl: '/leaflet/marker-icon-green.png',
            shadowUrl: '/leaflet/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41],
        });
        const iconRed = new L.Icon({
            iconUrl: '/leaflet/marker-icon-red.png',
            shadowUrl: '/leaflet/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41],
        });

        function markerByStatus(status) {
            const s = String(status ?? '').toLowerCase().trim();
            if (['done','selesai','completed','finish','finished'].includes(s)) return iconGreen;
            if (['skipped','skip','rejected','reject','batal'].includes(s)) return iconRed;
            return iconBlue;
        }

        // Marker toko
        stops.forEach((s) => {
            if (!s.lat || !s.lng) return;
            const popup = `<b>${s.seq ? ('#' + s.seq + ' ') : ''}${s.name ?? 'Toko'}</b><br/>
                ${s.address ?? ''}<br/>
                Status: <b>${s.status ?? '-'}</b>`;
            L.marker([s.lat, s.lng], { icon: markerByStatus(s.status) })
                .addTo(map)
                .bindPopup(popup);
            bounds.extend([s.lat, s.lng]);
        });

        // Polyline ORS (GeoJSON) - MERAH
        if (geojson && geojson.type) {
            const routeLayer = L.geoJSON(geojson, {
                style: { color: '#ef4444', weight: 4, opacity: 0.7 }
            }).addTo(map);
            try {
                const rb = routeLayer.getBounds();
                if (rb && rb.isValid()) bounds.extend(rb);
            } catch (e) {}
        }

        // Marker Driver (Real-time) - saat map pertama dibuka
        const driver = @json($driver);
        if (driver && driver.lat && driver.lng) {
            L.circleMarker([driver.lat, driver.lng], {
                radius: 11, fillColor: "#f97316", color: "#ffffff",
                weight: 2, opacity: 1, fillOpacity: 0.9
            })
            .addTo(map)
            .bindPopup('<b>Lokasi Driver Saat Ini</b><br>Update terakhir: ' + driver.time);
        }

        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }

        // Store map reference for replay
        window['_map_' + tripId] = map;
    }

    document.addEventListener('DOMContentLoaded', initTripMap);
    window.addEventListener('livewire:navigated', initTripMap);
})();
</script>
@endpush

{{-- Replay Script (only loaded if GPS history exists) --}}
@if($hasLocationHistory)
@push('scripts')
<script>
(function() {
    const tripId = @json($trip->id);
    const historyUrl = @json($locationHistoryUrl);

    // State
    let trackPoints = [];
    let replayTimer = null;
    let currentIdx = 0;
    let replayMarker = null;
    let replayTrail = null;
    let isLoaded = false;

    // DOM references (lazy, fetched on first play)
    const getEl = (id) => document.getElementById(id + '-' + tripId);

    function formatTime(isoString) {
        if (!isoString) return '-';
        const d = new Date(isoString);
        return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function updateSlider() {
        const slider = getEl('replay-slider');
        if (!slider || !trackPoints.length) return;
        slider.max = trackPoints.length - 1;
        slider.value = currentIdx;
    }

    function updateStatus(text) {
        const el = getEl('replay-status');
        if (el) el.textContent = text;
    }

    function updateTimeDisplay() {
        const el = getEl('replay-time');
        if (!el || !trackPoints[currentIdx]) return;
        el.textContent = formatTime(trackPoints[currentIdx].time) +
            ' (' + (currentIdx + 1) + '/' + trackPoints.length + ')';
    }

    function setPlayPauseState(playing) {
        const playBtn  = getEl('replay-play');
        const pauseBtn = getEl('replay-pause');
        if (playing) {
            playBtn?.classList.add('hidden');
            pauseBtn?.classList.remove('hidden');
        } else {
            playBtn?.classList.remove('hidden');
            pauseBtn?.classList.add('hidden');
        }
    }

    async function loadHistory() {
        if (isLoaded) return true;
        updateStatus('Memuat data GPS...');
        try {
            const res = await fetch(historyUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!json.success || !json.data?.length) {
                updateStatus('Tidak ada data GPS.');
                return false;
            }
            trackPoints = json.data;
            isLoaded = true;
            updateStatus('(' + trackPoints.length + ' titik GPS)');
            return true;
        } catch (e) {
            updateStatus('Gagal memuat data GPS.');
            return false;
        }
    }

    function moveMarker(idx) {
        const map = window['_map_' + tripId];
        if (!map || !trackPoints[idx]) return;

        const pt = trackPoints[idx];
        const latlng = [pt.lat, pt.lng];

        // Draw replay trail (biru)
        if (!replayTrail) {
            replayTrail = L.polyline([], { color: '#3b82f6', weight: 3, opacity: 0.7, dashArray: '6 4' }).addTo(map);
        }
        replayTrail.addLatLng(latlng);

        // Move/create replay marker (kuning)
        if (!replayMarker) {
            replayMarker = L.circleMarker(latlng, {
                radius: 10, fillColor: '#eab308', color: '#ffffff',
                weight: 2, opacity: 1, fillOpacity: 1
            }).addTo(map).bindPopup('Replay: ' + formatTime(pt.time));
        } else {
            replayMarker.setLatLng(latlng);
            replayMarker._popup?.setContent('Replay: ' + formatTime(pt.time));
        }

        updateSlider();
        updateTimeDisplay();
        currentIdx = idx;
    }

    function stopReplay() {
        if (replayTimer) { clearInterval(replayTimer); replayTimer = null; }
        setPlayPauseState(false);
    }

    function startReplay() {
        if (!trackPoints.length) return;
        if (currentIdx >= trackPoints.length - 1) currentIdx = 0;

        setPlayPauseState(true);

        const speed = parseInt(getEl('replay-speed')?.value ?? 5);
        const intervalMs = Math.max(50, Math.round(500 / speed)); // base 500ms per step

        replayTimer = setInterval(() => {
            if (currentIdx >= trackPoints.length - 1) {
                stopReplay();
                updateStatus('Selesai ✓');
                return;
            }
            currentIdx++;
            moveMarker(currentIdx);
        }, intervalMs);
    }

    // Public control function
    window['replayControl_' + tripId] = async function(action) {
        if (action === 'play') {
            if (!isLoaded) {
                const ok = await loadHistory();
                if (!ok) return;
                moveMarker(0); // show first point
            }
            startReplay();
        } else if (action === 'pause') {
            stopReplay();
            updateStatus('Dijeda');
        } else if (action === 'reset') {
            stopReplay();
            currentIdx = 0;
            if (replayTrail) { replayTrail.setLatLngs([]); }
            if (replayMarker && trackPoints[0]) {
                replayMarker.setLatLng([trackPoints[0].lat, trackPoints[0].lng]);
            }
            updateSlider();
            updateTimeDisplay();
            updateStatus('');
        }
    };

    // Slider scrub support
    document.addEventListener('DOMContentLoaded', () => {
        const slider = getEl('replay-slider');
        if (!slider) return;
        slider.addEventListener('input', async (e) => {
            if (!isLoaded) {
                const ok = await loadHistory();
                if (!ok) return;
            }
            stopReplay();
            const idx = parseInt(e.target.value);
            moveMarker(idx);
            updateStatus('Dijeda (scrub)');
        });
    });
})();
</script>
@endpush
@endif
