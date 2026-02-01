@php
    $payload = ($trips ?? collect())->map(function ($trip) {
        $warehouse = [
            'lat' => (float) ($trip->start_lat ?? 0),
            'lng' => (float) ($trip->start_lng ?? 0),
        ];

        $stops = $trip->stops
            ->sortBy('sequence')
            ->map(fn ($s) => [
                'name' => $s->store?->name,
                'address' => $s->store?->address,
                'lat' => (float) ($s->store?->lat ?? 0),
                'lng' => (float) ($s->store?->lng ?? 0),
                'status' => $s->status,
                'seq' => $s->sequence,
            ])->values()->all();

        $geo = $trip->route_geojson ? json_decode($trip->route_geojson, true) : null;

        return [
            'id' => $trip->id,
            'mapId' => 'mon-map-' . $trip->id,
            'warehouse' => $warehouse,
            'stops' => $stops,
            'geojson' => $geo,
            'driverName' => $trip->driver?->name ?? '—',
            'driverLat' => (float) $trip->current_lat,
            'driverLng' => (float) $trip->current_lng,
            'driverTime' => $trip->updated_at->format('H:i:s'),
            'doneCount' => $trip->stops->where('status', 'done')->count(),
            'skipCount' => $trip->stops->where('status', 'skipped')->count(),
            'totalStops' => $trip->stops->count(),
            'url' => \App\Filament\Resources\TripResource::getUrl('view', ['record' => $trip]),
        ];
    })->values()->all();
@endphp

@if(empty($payload))
    <div class="text-sm text-gray-400">Tidak ada trip yang sedang berjalan.</div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($payload as $t)
            <a href="{{ $t['url'] }}" class="block">
                <div class="rounded-xl border border-gray-800 bg-gray-900/30 hover:bg-gray-900/50 transition overflow-hidden">
                    <div class="p-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-gray-400">Driver</div>
                            <div class="font-semibold">{{ $t['driverName'] }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                Trip #{{ $t['id'] }} • ON GOING
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs text-gray-400">Progress</div>
                            <div class="text-sm font-semibold text-gray-200">
                                {{ $t['doneCount'] + $t['skipCount'] }} / {{ $t['totalStops'] }}
                            </div>
                            <div class="text-[11px] text-gray-500">
                                Done {{ $t['doneCount'] }} • Skip {{ $t['skipCount'] }}
                            </div>
                        </div>
                    </div>

                    <div class="px-4 pb-4">
                        <div id="{{ $t['mapId'] }}" style="height: 190px; width: 100%; border-radius: 12px; overflow:hidden;"></div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    @push('scripts')
        <script>
            window.__monitorTripsPayload = @json($payload);
        </script>

        <script>
            (function () {
                function initMapForTrip(t) {
                    const el = document.getElementById(t.mapId);
                    if (!el) return;

                    if (el._leaflet_map) {
                        el._leaflet_map.remove();
                        el._leaflet_map = null;
                    }

                    const warehouse = t.warehouse ?? {};
                    const stops = t.stops ?? [];
                    const geojson = t.geojson ?? null;

                    const firstStop = stops[0] ?? {};
                    const centerLat = warehouse.lat || firstStop.lat || 0;
                    const centerLng = warehouse.lng || firstStop.lng || 0;

                    if (!centerLat || !centerLng) {
                        el.innerHTML = '<div style="padding:10px">Koordinat belum lengkap.</div>';
                        return;
                    }

                    const map = L.map(t.mapId, {
                        zoomControl: false,
                        attributionControl: false,
                        scrollWheelZoom: false,
                        doubleClickZoom: false,
                    }).setView([centerLat, centerLng], 13);

                    el._leaflet_map = map;

L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                attribution: 'Google Maps'
            }).addTo(map);

                    const bounds = L.latLngBounds([]);
                    const shadow = '/leaflet/marker-shadow.png';

                    const iconBlue = new L.Icon({
                        iconUrl: '/leaflet/marker-icon-blue.png',
                        shadowUrl: shadow,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41],
                    });

                    const iconGreen = new L.Icon({
                        iconUrl: '/leaflet/marker-icon-green.png',
                        shadowUrl: shadow,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41],
                    });

                    const iconRed = new L.Icon({
                        iconUrl: '/leaflet/marker-icon-red.png',
                        shadowUrl: shadow,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41],
                    });

                    function markerByStatus(status) {
                        const s = String(status ?? '').toLowerCase().trim();
                        if (s === 'done') return iconGreen;
                        if (s === 'skipped') return iconRed;
                        return iconBlue;
                    }

                    if (warehouse.lat && warehouse.lng) {
                        L.marker([warehouse.lat, warehouse.lng]).addTo(map);
                        bounds.extend([warehouse.lat, warehouse.lng]);
                    }

                    stops.forEach(s => {
                        if (!s.lat || !s.lng) return;
                        L.marker([s.lat, s.lng], { icon: markerByStatus(s.status) }).addTo(map);
                        bounds.extend([s.lat, s.lng]);
                    });

                    if (geojson && geojson.type) {
                        const routeLayer = L.geoJSON(geojson).addTo(map);
                        try {
                            const rb = routeLayer.getBounds();
                            if (rb && rb.isValid()) bounds.extend(rb);
                        } catch (e) {}
                    }

                    if (bounds.isValid()) {
                        map.fitBounds(bounds, { padding: [10, 10] });
                    }

                    // Marker Driver
                    if (t.driverLat && t.driverLng) {
                        L.circleMarker([t.driverLat, t.driverLng], {
                            radius: 6,
                            fillColor: "#3b82f6",
                            color: "#ffffff",
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.9
                        })
                        .addTo(map)
                        .bindPopup('<b>' + t.driverName + '</b><br>Update: ' + t.driverTime);
                    }
                }

                function boot() {
                    if (typeof L === 'undefined') return;
                    const trips = window.__monitorTripsPayload || [];
                    trips.forEach(initMapForTrip);
                }

                document.addEventListener('DOMContentLoaded', boot);
                window.addEventListener('livewire:navigated', boot);
            })();
        </script>
    @endpush
@endif
