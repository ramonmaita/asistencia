<?php

namespace App\Filament\App\Resources\Estudiantes\Pages;

use App\Actions\ImportarEstudiantes;
use App\Filament\App\Resources\Estudiantes\EstudianteResource;
use App\Models\Periodo;
use App\Models\Seccion;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEstudiantes extends ListRecords
{
    protected static string $resource = EstudianteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                $this->importarAction(),
                $this->descargarPlantillaAction(),
            ])
                ->label('Importar')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->button()
                ->color('gray'),
            CreateAction::make(),
        ];
    }

    private function importarAction(): Action
    {
        return Action::make('importar')
            ->label('Importar desde Excel o CSV')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->visible(fn (): bool => EstudianteResource::canCreate())
            ->modalDescription('Columnas: cédula, nombres, apellidos y, opcionalmente, correo y teléfono. Los estudiantes que ya existan en el PNF se actualizan.')
            ->schema([
                FileUpload::make('archivo')
                    ->label('Archivo')
                    ->disk('local')
                    ->directory('importaciones')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(5120)
                    ->required(),
                Select::make('seccion_id')
                    ->label('Inscribir en la sección (opcional)')
                    ->options(fn (): array => Seccion::query()
                        ->with('trayecto')
                        ->whereHas('periodo', fn (Builder $periodos) => $periodos->where('activo', true))
                        ->orderBy('codigo')
                        ->get()
                        ->mapWithKeys(fn (Seccion $seccion): array => [$seccion->id => "{$seccion->codigo} · {$seccion->trayecto->nombre}"])
                        ->all())
                    ->helperText(fn (): string => 'Secciones del periodo '.(Periodo::activo()->value('nombre') ?? 'activo').'.'),
            ])
            ->action(function (array $data, ImportarEstudiantes $importar): void {
                $disco = Storage::disk('local');

                try {
                    $resultado = $importar->handle(
                        $disco->path($data['archivo']),
                        Filament::getTenant(),
                        filled($data['seccion_id']) ? Seccion::findOrFail($data['seccion_id']) : null,
                    );
                } finally {
                    $disco->delete($data['archivo']);
                }

                $resumen = "Creados: {$resultado->creados} · Actualizados: {$resultado->actualizados}";

                if (filled($data['seccion_id'])) {
                    $resumen .= " · Inscritos: {$resultado->inscritos}";
                }

                if (! $resultado->tieneErrores()) {
                    Notification::make()->title('Importación completada')->body($resumen)->success()->send();

                    return;
                }

                $detalle = collect($resultado->errores)
                    ->take(15)
                    ->map(fn (array $error): string => "Fila {$error['fila']}".($error['cedula'] ? " ({$error['cedula']})" : '').": {$error['mensaje']}")
                    ->implode("\n");

                if (count($resultado->errores) > 15) {
                    $detalle .= "\n… y ".(count($resultado->errores) - 15).' errores más.';
                }

                Notification::make()
                    ->title('Importación con '.count($resultado->errores).' filas rechazadas')
                    ->body("{$resumen}\n\n{$detalle}")
                    ->warning()
                    ->persistent()
                    ->send();
            });
    }

    private function descargarPlantillaAction(): Action
    {
        return Action::make('descargarPlantilla')
            ->label('Descargar plantilla')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                $salida = fopen('php://output', 'w');
                fwrite($salida, "\xEF\xBB\xBF");
                fputcsv($salida, ImportarEstudiantes::COLUMNAS, ';');
                fputcsv($salida, ['30111222', 'María José', 'Pérez Rodríguez', 'maria@ejemplo.com', '0414-1234567'], ';');
                fclose($salida);
            }, 'plantilla_estudiantes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']));
    }
}
