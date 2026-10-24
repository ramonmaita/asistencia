<?php

namespace App\Filament\Admin\Resources\Sedes\Pages;

use App\Filament\Admin\Resources\Sedes\SedeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSedes extends ListRecords
{
    protected static string $resource = SedeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
