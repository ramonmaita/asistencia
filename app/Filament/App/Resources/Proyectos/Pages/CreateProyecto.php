<?php

namespace App\Filament\App\Resources\Proyectos\Pages;

use App\Filament\App\Resources\Proyectos\ProyectoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProyecto extends CreateRecord
{
    protected static string $resource = ProyectoResource::class;

    protected function getRedirectUrl(): string
    {
        return ProyectoResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
