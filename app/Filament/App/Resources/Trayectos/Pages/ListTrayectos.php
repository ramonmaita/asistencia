<?php

namespace App\Filament\App\Resources\Trayectos\Pages;

use App\Filament\App\Resources\Trayectos\TrayectoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrayectos extends ListRecords
{
    protected static string $resource = TrayectoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
