<?php

namespace App\Filament\Admin\Resources\Pnfs\Pages;

use App\Filament\Admin\Resources\Pnfs\PnfResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPnfs extends ListRecords
{
    protected static string $resource = PnfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
