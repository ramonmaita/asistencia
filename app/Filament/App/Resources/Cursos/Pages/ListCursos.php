<?php

namespace App\Filament\App\Resources\Cursos\Pages;

use App\Actions\GenerarSesionesClase;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Models\Curso;
use App\Models\Periodo;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCursos extends ListRecords
{
    protected static string $resource = CursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->generarSesionesAction(),
            CreateAction::make(),
        ];
    }

    private function generarSesionesAction(): Action
    {
        return Action::make('generarSesiones')
            ->label('Generar sesiones del periodo')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->color('gray')
            ->visible(fn (): bool => auth()->user()->coordinaPnfActual())
            ->requiresConfirmation()
            ->modalDescription(fn (): string => 'Se crean las sesiones de todos los cursos del periodo '.(Periodo::activo()->value('nombre') ?? 'activo').' según su horario, saltando los feriados. Las sesiones existentes no se modifican.')
            ->action(function (GenerarSesionesClase $generar): void {
                $cursos = Curso::query()->delPeriodoActivo()->with(['seccion.periodo', 'horarios'])->get();
                $creadas = $cursos->sum(fn (Curso $curso): int => $generar->handle($curso));

                Notification::make()
                    ->title("Se crearon {$creadas} sesiones")
                    ->body("Cursos revisados: {$cursos->count()}.")
                    ->success()
                    ->send();
            });
    }
}
