<?php

namespace App\Filament\App\Resources\UnidadCurriculars\Pages;

use App\Filament\App\Resources\UnidadCurriculars\UnidadCurricularResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnidadCurriculars extends ListRecords
{
    protected static string $resource = UnidadCurricularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
