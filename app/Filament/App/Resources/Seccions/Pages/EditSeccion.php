<?php

namespace App\Filament\App\Resources\Seccions\Pages;

use App\Actions\GenerarCarnets;
use App\Filament\App\Resources\Seccions\SeccionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditSeccion extends EditRecord
{
    protected static string $resource = SeccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimirCarnets')
                ->label('Carnets de la sección')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->disabled(fn (): bool => ! $this->record->estudiantes()->exists())
                ->action(fn (GenerarCarnets $generarCarnets): StreamedResponse => response()->streamDownload(
                    fn () => print ($generarCarnets->handle($this->record->estudiantes()->get())),
                    "carnets_{$this->record->codigo}.pdf",
                    ['Content-Type' => 'application/pdf'],
                )),
            DeleteAction::make(),
        ];
    }
}
