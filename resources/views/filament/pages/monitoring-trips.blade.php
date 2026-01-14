<x-filament::page>
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-sm text-gray-400">
            Menampilkan maksimal 12 trip yang sedang berjalan (auto refresh tiap 30 detik).
        </div>
    </div>

    @include('filament.components.monitoring-trips-grid', ['trips' => $trips])
</x-filament::page>
                