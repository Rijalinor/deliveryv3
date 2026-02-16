<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use App\Models\TripStop;
use Filament\Resources\Pages\CreateRecord;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // store_ids bukan kolom trips
        $this->storeIds = $data['store_ids'] ?? [];
        unset($data['store_ids']);

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

    protected array $storeIds = [];
}
