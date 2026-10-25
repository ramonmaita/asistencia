<?php

namespace App\Filament\App\Support;

use App\Actions\ImportarEstudiantes;
use App\Actions\ResultadoImportacion;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Piezas comunes de la importación de estudiantes, que se usa desde la lista
 * de estudiantes (coordinador) y desde cada curso (docente).
 */
final class ImportacionDeEstudiantes
{
    public const DESCRIPCION = 'Columnas: cédula, nombres, apellidos y, opcionalmente, correo y teléfono. Los estudiantes que ya existan en el PNF se actualizan.';

    public static function campoArchivo(): FileUpload
    {
        return FileUpload::make('archivo')
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
            ->required();
    }

    /**
     * Avisa el resultado; con errores muestra hasta 15 filas rechazadas.
     */
    public static function notificar(ResultadoImportacion $resultado, bool $conInscritos): void
    {
        $resumen = "Creados: {$resultado->creados} · Actualizados: {$resultado->actualizados}";

        if ($conInscritos) {
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
    }

    public static function plantillaAction(): Action
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
