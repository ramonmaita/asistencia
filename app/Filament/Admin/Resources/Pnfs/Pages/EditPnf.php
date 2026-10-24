<?php

namespace App\Filament\Admin\Resources\Pnfs\Pages;

use App\Filament\Admin\Resources\Pnfs\PnfResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPnf extends EditRecord
{
    protected static string $resource = PnfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
