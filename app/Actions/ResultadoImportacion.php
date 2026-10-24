<?php

namespace App\Actions;

/**
 * Resumen de una importación de estudiantes.
 */
final class ResultadoImportacion
{
    public int $creados = 0;

    public int $actualizados = 0;

    public int $inscritos = 0;

    /** @var array<int, array{fila: int, cedula: ?string, mensaje: string}> */
    public array $errores = [];

    public function agregarError(int $fila, ?string $cedula, string $mensaje): void
    {
        $this->errores[] = ['fila' => $fila, 'cedula' => $cedula, 'mensaje' => $mensaje];
    }

    public function tieneErrores(): bool
    {
        return $this->errores !== [];
    }
}
