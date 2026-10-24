<?php

namespace App\Filament\Admin\Resources\Periodos\Pages;

use App\Filament\Admin\Resources\Periodos\PeriodoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPeriodo extends EditRecord
{
    protected static string $resource = PeriodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
