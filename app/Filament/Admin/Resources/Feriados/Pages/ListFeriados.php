<?php

namespace App\Filament\Admin\Resources\Feriados\Pages;

use App\Filament\Admin\Resources\Feriados\FeriadoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeriados extends ListRecords
{
    protected static string $resource = FeriadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
