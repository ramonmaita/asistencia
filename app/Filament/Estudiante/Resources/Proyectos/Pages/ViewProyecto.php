<?php

namespace App\Filament\Estudiante\Resources\Proyectos\Pages;

use App\Filament\Estudiante\Resources\Proyectos\ProyectoResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProyecto extends ViewRecord
{
    protected static string $resource = ProyectoResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->titulo;
    }
}
