<?php

namespace App\Filament\Driver\Resources\DriverTripResource\Pages;

use App\Filament\Driver\Resources\DriverTripResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDriverTrip extends EditRecord
{
    protected static string $resource = DriverTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('startTrip')
                ->label('Mulai Trip')
                ->visible(fn () => $this->record->status === 'planned')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'on_going']);

                    Notification::make()
                        ->title('Trip dimulai')
                        ->success()
                        ->send();
                }),
        ];
    }
}
