<?php

namespace App\Filament\Driver\Resources\DriverTripResource\Pages;

use App\Filament\Driver\Resources\DriverTripResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\TripStop;

class CreateDriverTrip extends CreateRecord
{
    protected static string $resource = DriverTripResource::class;

    protected array $storeIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $state = $this->form->getState();

        $this->storeIds = $state['store_ids'] ?? [];
    
        $data['driver_id'] = Auth::id();
        $data['status'] = 'planned';
    
        return $data;
    }

    protected function afterCreate(): void
    {
        $trip = $this->record;

        $storeIds = collect($this->storeIds ?? [])
            ->unique()
            ->values()
            ->all();

        foreach ($storeIds as $storeId) {
            TripStop::create([
                'trip_id' => $trip->id,
                'store_id' => $storeId,
                'status' => 'pending',
            ]);
        }
    }
}
