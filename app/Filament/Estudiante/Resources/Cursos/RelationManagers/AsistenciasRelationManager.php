<?php

namespace App\Filament\Estudiante\Resources\Cursos\RelationManagers;

use App\Filament\Estudiante\Resources\Concerns\HistorialDeAsistencias;

/**
 * Histórico de asistencias del estudiante a las clases del curso.
 */
class AsistenciasRelationManager extends HistorialDeAsistencias
{
    protected function tablaDeSesiones(): string
    {
        return 'sesiones_clase';
    }
}
