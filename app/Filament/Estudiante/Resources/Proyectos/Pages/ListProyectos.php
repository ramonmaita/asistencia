<?php

namespace App\Filament\Estudiante\Resources\Proyectos\Pages;

use App\Filament\Estudiante\Resources\Proyectos\ProyectoResource;
use Filament\Resources\Pages\ListRecords;

class ListProyectos extends ListRecords
{
    protected static string $resource = ProyectoResource::class;

    protected ?string $heading = 'Mi proyecto';
}
