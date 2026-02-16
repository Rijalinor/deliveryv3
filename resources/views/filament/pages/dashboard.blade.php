<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Card --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-sparkles class="w-6 h-6 text-primary-500" />
                    <span>Welcome to DeliveryV3</span>
                </div>
            </x-slot>
            
            <div class="grid gap-4 md:grid-cols-3">
                <a href="{{ \App\Filament\Resources\TripResource::getUrl('create') }}" 
                   class="group flex flex-col items-center p-6 transition-all duration-300 bg-white border-2 border-gray-200 rounded-xl hover:border-primary-500 hover:shadow-xl hover:scale-105 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-500">
                    <div class="p-3 mb-3 transition-colors rounded-full bg-primary-50 dark:bg-primary-950 group-hover:bg-primary-100 dark:group-hover:bg-primary-900">
                        <x-heroicon-o-plus-circle class="w-12 h-12 text-primary-500" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white">Create Trip</h3>
                    <p class="text-sm text-center text-gray-500 dark:text-gray-400">Start a new delivery trip</p>
                </a>
                
                <a href="{{ route('filament.admin.pages.monitoring-trips') }}" 
                   class="group flex flex-col items-center p-6 transition-all duration-300 bg-white border-2 border-gray-200 rounded-xl hover:border-warning-500 hover:shadow-xl hover:scale-105 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-warning-500">
                    <div class="p-3 mb-3 transition-colors rounded-full bg-warning-50 dark:bg-warning-950 group-hover:bg-warning-100 dark:group-hover:bg-warning-900">
                        <x-heroicon-o-map class="w-12 h-12 text-warning-500" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white">Monitoring</h3>
                    <p class="text-sm text-center text-gray-500 dark:text-gray-400">Track active deliveries</p>
                </a>
                
                <a href="{{ \App\Filament\Resources\GoodsIssueResource::getUrl('index') }}" 
                   class="group flex flex-col items-center p-6 transition-all duration-300 bg-white border-2 border-gray-200 rounded-xl hover:border-success-500 hover:shadow-xl hover:scale-105 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-success-500">
                    <div class="p-3 mb-3 transition-colors rounded-full bg-success-50 dark:bg-success-950 group-hover:bg-success-100 dark:group-hover:bg-success-900">
                        <x-heroicon-o-document-text class="w-12 h-12 text-success-500" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white">Goods Issues</h3>
                    <p class="text-sm text-center text-gray-500 dark:text-gray-400">Manage GI documents</p>
                </a>
            </div>
        </x-filament::section>

        {{-- Widgets --}}
        <x-filament-widgets::widgets
            :columns="$this->getColumns()"
            :data="
                [
                    ...(property_exists($this, 'filters') ? ['filters' => $this->filters] : []),
                ]
            "
            :widgets="$this->getVisibleWidgets()"
        />
    </div>
</x-filament-panels::page>
