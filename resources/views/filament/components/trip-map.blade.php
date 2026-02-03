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
'name' => $s->store?->name,
'address' => $s->store?->address,
'lat' => (float) ($s->store?->lat ?? 0),
'lng' => (float) ($s->store?->lng ?? 0),
'status' => $s->status,
'seq' => $s->sequence,
])
->values()
->all();

$geo = $trip->route_geojson ? json_decode($trip->route_geojson, true) : null;

$driver = null;

if ($trip->current_lat && $trip->current_lng) {
    $driver = [
        'lat' => (float) $trip->current_lat,
        'lng' => (float) $trip->current_lng,
        'time' => $trip->updated_at->format('H:i:s'),
    ];
} else {
    $latestLocation = \App\Models\DriverLocation::where('trip_id', $trip->id)
        ->latest()
        ->first();

    $driver = $latestLocation ? [
        'lat' => (float) $latestLocation->lat,
        'lng' => (float) $latestLocation->lng,
        'time' => $latestLocation->created_at->format('H:i:s'),
    ] : null;
}

$mapId = 'trip-map-' . $trip->id;
@endphp


<div class="rounded-xl border border-gray-800 overflow-hidden">
    <div id="{{ $mapId }}" style="height: 520px; width: 100%;"></div>
</div>

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
        const shadow = '/leaflet/marker-shadow.png';

        const iconBlue = new L.Icon({
            iconUrl: '/leaflet/marker-icon-blue.png',
            shadowUrl: '/leaflet/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        });

        const iconGreen = new L.Icon({
            iconUrl: '/leaflet/marker-icon-green.png',
            shadowUrl: '/leaflet/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        });

        const iconRed = new L.Icon({
            iconUrl: '/leaflet/marker-icon-red.png',
            shadowUrl: '/leaflet/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        });

        function markerByStatus(status) {
            const s = String(status ?? '').toLowerCase().trim();

            // sesuaikan kalau status kamu beda
            if (['done','selesai','completed','finish','finished'].includes(s)) return iconGreen;
            if (['skipped','skip','rejected','reject','batal'].includes(s)) return iconRed;

            return iconBlue; // pending / default
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

        // Polyline ORS (GeoJSON)
        if (geojson && geojson.type) {
            const routeLayer = L.geoJSON(geojson).addTo(map);
            try {
                const rb = routeLayer.getBounds();
                if (rb && rb.isValid()) bounds.extend(rb);
            } catch (e) {}
        }

        // Marker Driver (Real-time)
        const driver = @json($driver);
        if (driver && driver.lat && driver.lng) {
            L.circleMarker([driver.lat, driver.lng], {
                radius: 11,
                fillColor: "#f97316", // Orange
                color: "#ffffff",
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            })
            .addTo(map)
            .bindPopup('<b>Lokasi Driver Saat Ini</b><br>Update terakhir: ' + driver.time);
            
            // Opsional: jangan masukkan driver ke bounds agar map tidak zoom out terlalu jauh
            // bounds.extend([driver.lat, driver.lng]);
        }

        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }
    }

    // jalanin saat halaman siap
    document.addEventListener('DOMContentLoaded', initTripMap);
    window.addEventListener('livewire:navigated', initTripMap);
})();
</script>
@endpush
