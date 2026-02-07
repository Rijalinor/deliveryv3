<?php

namespace App\Filament\Resources\GoodsIssueResource\Pages;

use App\Filament\Resources\GoodsIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoodsIssue extends EditRecord
{
    protected static string $resource = GoodsIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
