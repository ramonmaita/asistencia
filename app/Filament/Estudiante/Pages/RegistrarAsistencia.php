<?php

namespace App\Filament\Estudiante\Pages;

use App\Actions\RegistrarAsistenciaPorQr;
use App\Enums\MetodoAsistencia;
use App\Support\CodigoQrDinamico;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * El estudiante lee con la cámara el QR dinámico que proyecta el docente;
 * se envía su ubicación porque la sede puede exigirla.
 */
class RegistrarAsistencia extends Page
{
    protected string $view = 'filament.estudiante.pages.registrar-asistencia';

    protected static ?string $title = 'Registrar asistencia';

    protected static ?string $slug = 'registrar-asistencia';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    /**
     * @return array{exito: bool, mensaje: string}
     */
    public function registrar(string $codigo, ?float $latitud = null, ?float $longitud = null): array
    {
        try {
            $sesion = app(CodigoQrDinamico::class)->resolver($codigo);

            $resultado = app(RegistrarAsistenciaPorQr::class)->handle(
                $sesion,
                Filament::auth()->user(),
                MetodoAsistencia::QrDinamico,
                latitud: $latitud,
                longitud: $longitud,
            );
        } catch (ValidationException $error) {
            return ['exito' => false, 'mensaje' => collect($error->errors())->flatten()->first()];
        }

        return [
            'exito' => true,
            'mensaje' => $resultado['nueva']
                ? "Asistencia registrada: {$resultado['asistencia']->estado->getLabel()} · {$sesion->descripcion()}"
                : "Ya tenías tu asistencia registrada · {$sesion->descripcion()}",
        ];
    }
}
