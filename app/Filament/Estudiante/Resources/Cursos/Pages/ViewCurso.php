<?php

namespace App\Filament\Estudiante\Resources\Cursos\Pages;

use App\Filament\Estudiante\Resources\Cursos\CursoResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCurso extends ViewRecord
{
    protected static string $resource = CursoResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->unidadCurricular->nombre;
    }
}
