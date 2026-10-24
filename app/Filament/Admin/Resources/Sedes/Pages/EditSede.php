<?php

namespace App\Filament\Admin\Resources\Sedes\Pages;

use App\Filament\Admin\Resources\Sedes\SedeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSede extends EditRecord
{
    protected static string $resource = SedeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
