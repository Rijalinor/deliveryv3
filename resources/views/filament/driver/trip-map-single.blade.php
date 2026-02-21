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

<div class="relative" wire:ignore>
    <div id="{{ $mapId }}" class="w-full shadow-inner" style="height: 350px;"></div>
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
    let driverMarker = null;

    // Custom Icon untuk Driver (🚛 Emoji)
    const driverIcon = L.divIcon({
        html: '<div style="font-size: 32px; line-height: 1;">🚛</div>',
        className: 'driver-emoji-marker',
        iconSize: [40, 40],
        iconAnchor: [20, 30],
        popupAnchor: [0, -30]
    });

    function ensureMap() {
        const el = document.getElementById(mapId);
        if (!el) return null;

        if (!window.__deliveryMaps[mapId]) {
            const map = L.map(mapId).setView([currentDest.lat, currentDest.lng], 15);

            // Google Streets
            L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                attribution: 'Google Maps'
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

        // Update view if driver marker doesn't exist yet
        if (!driverMarker) {
            obj.map.setView([dest.lat, dest.lng], obj.map.getZoom());
        }
        setTimeout(() => obj.map.invalidateSize(), 50);
    }

    let driverAnimId = null;
    function animateMarker(marker, newPos) {
        if (!marker) return;
        if (driverAnimId) cancelAnimationFrame(driverAnimId);
        
        const startPos = marker.getLatLng();
        const startTime = performance.now();
        const duration = 2000; // 2 seconds animation

        function step(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Linear Interpolation (Lerp)
            const lat = startPos.lat + (newPos.lat - startPos.lat) * progress;
            const lng = startPos.lng + (newPos.lng - startPos.lng) * progress;

            marker.setLatLng([lat, lng]);

            if (progress < 1) {
                driverAnimId = requestAnimationFrame(step);
            } else {
                driverAnimId = null;
            }
        }
        driverAnimId = requestAnimationFrame(step);
    }

    function updateDriverLocation(lat, lng) {
        const obj = ensureMap();
        if (!obj) return;

        const newLatLng = L.latLng(lat, lng);

        if (!driverMarker) {
            driverMarker = L.marker(newLatLng, { icon: driverIcon }).addTo(obj.map)
                .bindPopup('<b>Lokasi Saya</b>');
        } else {
            // ✅ PAKAI ANIMASI MULUS
            animateMarker(driverMarker, newLatLng);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        ensureMap();
    });

    // ✅ event dari Livewire (koordinat toko)
    window.addEventListener('run-trip-refresh', (e) => {
        const dest = e?.detail?.dest ?? (e?.detail?.[0]?.dest) ?? null;
        updateTo(dest);
    });

    // ✅ event lokasi driver
    window.addEventListener('driver-location-updated', (e) => {
        const lat = e?.detail?.lat ?? (e?.detail?.[0]?.lat);
        const lng = e?.detail?.lng ?? (e?.detail?.[0]?.lng);
        if (lat && lng) {
            updateDriverLocation(lat, lng);
        }
    });
})();
</script>
@endpush
