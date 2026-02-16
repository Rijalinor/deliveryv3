<?php

namespace App\Filament\Resources\GoodsIssueResource\Pages;

use App\Filament\Resources\GoodsIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsIssues extends ListRecords
{
    protected static string $resource = GoodsIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-m-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Excel File')
                        ->disk('local')
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);
                    \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\TripsImport, $path);

                    \Filament\Notifications\Notification::make()
                        ->title('Import Successful')
                        ->success()
                        ->send();
                }),
        ];
    }
}
