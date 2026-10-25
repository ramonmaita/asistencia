<?php

namespace App\Filament\Estudiante\Resources\Proyectos\RelationManagers;

use App\Filament\Estudiante\Resources\Concerns\HistorialDeAsistencias;
use Filament\Tables\Columns\TextColumn;

/**
 * Histórico de asistencias del estudiante a las tutorías de su proyecto.
 */
class TutoriasRelationManager extends HistorialDeAsistencias
{
    protected static ?string $title = 'Mis tutorías';

    protected function tablaDeSesiones(): string
    {
        return 'tutorias';
    }

    protected function columnasDeLaSesion(): array
    {
        return [
            TextColumn::make('sesionable.tema')
                ->label('Tema')
                ->wrap(),
        ];
    }
}
