<?php

namespace App\Filament\App\Resources\UnidadCurriculars\Pages;

use App\Filament\App\Resources\UnidadCurriculars\UnidadCurricularResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUnidadCurricular extends EditRecord
{
    protected static string $resource = UnidadCurricularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
