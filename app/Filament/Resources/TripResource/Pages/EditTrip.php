<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use App\Models\TripStop;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // isi store_ids dari trip_stops yang masih aktif
        $data['store_ids'] = $this->record->stops()
            ->whereNull('deleted_at')
            ->pluck('store_id')
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->storeIds = $data['store_ids'] ?? [];
        unset($data['store_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $trip = $this->record;

        $newIds = collect($this->storeIds)->unique()->values();
        $oldIds = $trip->stops()->whereNull('deleted_at')->pluck('store_id');

        // stop yang dihapus → soft delete
        $toDelete = $oldIds->diff($newIds);
        TripStop::where('trip_id', $trip->id)
            ->whereIn('store_id', $toDelete)
            ->whereNull('deleted_at')
            ->delete();

        // stop baru → create
        $toAdd = $newIds->diff($oldIds);
        foreach ($toAdd as $storeId) {
            TripStop::create([
                'trip_id' => $trip->id,
                'store_id' => $storeId,
                'status' => 'pending',
            ]);
        }
    }

    protected array $storeIds = [];
}
