<?php

namespace App\Filament\App\Resources\Trayectos\Pages;

use App\Filament\App\Resources\Trayectos\TrayectoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrayecto extends EditRecord
{
    protected static string $resource = TrayectoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
