<?php

namespace App\Filament\App\Resources\Cursos\Pages;

use App\Filament\App\Resources\Cursos\CursoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCurso extends EditRecord
{
    protected static string $resource = CursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
