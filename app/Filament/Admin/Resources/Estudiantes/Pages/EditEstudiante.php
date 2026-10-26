<?php

namespace App\Filament\Admin\Resources\Estudiantes\Pages;

use App\Filament\Admin\Resources\Estudiantes\EstudianteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEstudiante extends EditRecord
{
    protected static string $resource = EstudianteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
