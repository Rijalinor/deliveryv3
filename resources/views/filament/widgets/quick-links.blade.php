@php
    $links = [
        [
            'title' => 'Drivers',
            'desc'  => 'Kelola driver',
            'url'   => \App\Filament\Resources\UserResource::getUrl('index'),
            'icon'  => '🙎‍♂️',
        ],
        [
            'title' => 'Stores',
            'desc'  => 'Data toko & koordinat',
            'url'   => \App\Filament\Resources\StoreResource::getUrl('index'),
            'icon'  => '🏣',
        ],
        [
            'title' => 'Trips',
            'desc'  => 'Daftar trip',
            'url'   => \App\Filament\Resources\TripResource::getUrl('index'),
            'icon'  => '🚛',
        ],
        [
            'title' => 'Monitoring',
            'desc'  => 'Pantau trip berjalan',
            'url'   => url('/admin/monitoring-trips'),
            'icon'  => '🗺️',
        ],
    ];
@endphp

<x-filament::widget>
    <x-filament::section heading="Quick Access">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($links as $l)
                <a href="{{ $l['url'] }}"
                   class="block rounded-xl border border-gray-800 bg-gray-900/30 hover:bg-gray-900/50 transition p-4">
                    <div class="flex items-start gap-3">
                        <div class="text-2xl">{{ $l['icon'] }}</div>
                        <div>
                            <div class="font-semibold">{{ $l['title'] }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $l['desc'] }}</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament::widget>
