<?php

namespace App\Filament\App\Resources\Proyectos\Pages;

use App\Actions\GenerarActaProyecto;
use App\Filament\App\Resources\Proyectos\ProyectoResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewProyecto extends ViewRecord
{
    protected static string $resource = ProyectoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('acta')
                ->label('Acta de veredictos (PDF)')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->action(fn (GenerarActaProyecto $acta): StreamedResponse => $acta->handle($this->getRecord())),
            EditAction::make(),
        ];
    }
}
