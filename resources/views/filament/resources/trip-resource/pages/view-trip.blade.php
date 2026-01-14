<x-filament-panels::page>
    {{-- 1) Ringkasan / Infolist --}}
    {{ $this->infolist }}

    {{-- 2) Map --}}
    <div class="mt-6">
        @include('filament.components.trip-map', ['record' => $record])
    </div>

    {{-- 3) Stops relation managers --}}
    <div class="mt-6">
        <x-filament-panels::resources.relation-managers
            :relation-managers="$this->getRelationManagers()"
            :owner-record="$record"
            :page-class="static::class"
        />
    </div>
</x-filament-panels::page>
