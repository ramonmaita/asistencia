<?php

namespace App\Filament\App\Resources\Proyectos\Pages;

use App\Filament\App\Resources\Proyectos\ProyectoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProyecto extends EditRecord
{
    protected static string $resource = ProyectoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
