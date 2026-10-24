<?php

namespace App\Filament\Estudiante\Resources\Cursos\Pages;

use App\Filament\Estudiante\Resources\Cursos\CursoResource;
use Filament\Resources\Pages\ListRecords;

class ListCursos extends ListRecords
{
    protected static string $resource = CursoResource::class;

    protected ?string $heading = 'Mis asistencias';
}
