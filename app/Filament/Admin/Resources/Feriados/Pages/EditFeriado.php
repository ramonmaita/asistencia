<?php

namespace App\Filament\Admin\Resources\Feriados\Pages;

use App\Filament\Admin\Resources\Feriados\FeriadoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeriado extends EditRecord
{
    protected static string $resource = FeriadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
