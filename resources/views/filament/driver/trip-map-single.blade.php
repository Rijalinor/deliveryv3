@php
    $mapId = 'map_single_' . $trip->id;

    // initial dest untuk render pertama
    $dest = [
        'lat' => (float) $stop->store->lat,
        'lng' => (float) $stop->store->lng,
        'name' => $stop->store->name,
        'address' => $stop->store->address,
    ];
@endphp

<div class="rounded-xl border overflow-hidden" wire:ignore>
    <div id="{{ $mapId }}" style="height: 380px; width: 100%;"></div>
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
    window.__deliveryMaps = window.__deliveryMaps || {};

    const mapId = @json($mapId);
    let currentDest = @json($dest);

    function ensureMap() {
        const el = document.getElementById(mapId);
        if (!el) return null;

        if (!window.__deliveryMaps[mapId]) {
            const map = L.map(mapId).setView([currentDest.lat, currentDest.lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(map);

            const marker = L.marker([currentDest.lat, currentDest.lng]).addTo(map)
                .bindPopup(`<b>${currentDest.name}</b><br/>${currentDest.address ?? ''}`);

            window.__deliveryMaps[mapId] = { map, marker };
            setTimeout(() => map.invalidateSize(), 100);
        }

        return window.__deliveryMaps[mapId];
    }

    function updateTo(dest) {
        if (!dest) return;

        currentDest = dest;

        const obj = ensureMap();
        if (!obj) return;

        obj.marker.setLatLng([dest.lat, dest.lng]);
        obj.marker.setPopupContent(`<b>${dest.name}</b><br/>${dest.address ?? ''}`);

        obj.map.setView([dest.lat, dest.lng], obj.map.getZoom());
        setTimeout(() => obj.map.invalidateSize(), 50);
    }

    document.addEventListener('DOMContentLoaded', () => {
        ensureMap();
    });

    // ✅ event dari Livewire (yang membawa koordinat toko aktif terbaru)
    window.addEventListener('run-trip-refresh', (e) => {
        const dest = e?.detail?.dest ?? null;
        updateTo(dest);
    });
})();
</script>
@endpush
