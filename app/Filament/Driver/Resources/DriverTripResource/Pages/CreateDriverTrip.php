<?php

namespace App\Filament\Driver\Resources\DriverTripResource\Pages;

use App\Filament\Driver\Resources\DriverTripResource;
use App\Services\TripAssignmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateDriverTrip extends CreateRecord
{
    protected static string $resource = DriverTripResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            // 1. Extract GI Numbers
            $giInput = $data['gi_input'] ?? [];
            unset($data['gi_input']);

            // 2. Prepare Trip Data
            $data['driver_id'] = Auth::id();
            $data['status'] = 'planned';
            $data['gi_number'] = is_array($giInput) ? implode(', ', $giInput) : (string) $giInput;

            // 3. Create Trip
            $trip = static::getModel()::create($data);

            // 4. Process GIs (Generate Stops)
            if (! empty($giInput)) {
                $service = new TripAssignmentService;
                $giNumbers = is_array($giInput) ? $giInput : explode(',', (string) $giInput);

                // This might throw Exception, which will rollback transaction
                $service->processGiBasedTrip($trip, $giNumbers);
            }

            return $trip;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
