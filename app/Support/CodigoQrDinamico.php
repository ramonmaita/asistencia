<?php

namespace App\Support;

use App\Models\Contracts\Sesionable;
use App\Models\SesionClase;
use App\Models\Tutoria;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Código del QR dinámico que muestra el docente y escanean los estudiantes.
 *
 * Formato: "GPNFD:{tipo}:{id}:{ventana}:{firma}", donde la ventana es el
 * número de intervalos de N segundos desde el epoch y la firma es un HMAC
 * con la clave de la aplicación. Caduca en segundos, así que una foto
 * reenviada deja de servir enseguida, y no se puede fabricar sin la clave.
 */
class CodigoQrDinamico
{
    private const PREFIJO = 'GPNFD';

    /**
     * @return array{codigo: string, expira_at: Carbon, segundos: int}
     */
    public function generar(Sesionable $sesion, ?Carbon $ahora = null): array
    {
        $segundos = $this->segundos();
        $ventana = intdiv(($ahora ?? now())->getTimestamp(), $segundos);
        $tipo = $sesion->getMorphClass();

        return [
            'codigo' => implode(':', [self::PREFIJO, $tipo, $sesion->getKey(), $ventana, $this->firmar($tipo, $sesion->getKey(), $ventana)]),
            'expira_at' => Carbon::createFromTimestamp(($ventana + 1) * $segundos),
            'segundos' => $segundos,
        ];
    }

    /**
     * Devuelve la sesión del código si es auténtico y no ha caducado. La
     * consulta respeta el PNF actual, así que un código de otro PNF no se encuentra.
     *
     * @throws ValidationException
     */
    public function resolver(string $codigo, ?Carbon $ahora = null): Sesionable
    {
        $partes = explode(':', trim($codigo));

        if (count($partes) !== 5 || $partes[0] !== self::PREFIJO) {
            throw $this->error('El código no es un QR de asistencia válido.');
        }

        [, $tipo, $id, $ventana, $firma] = $partes;
        $clase = Relation::getMorphedModel($tipo);

        if (! in_array($clase, [SesionClase::class, Tutoria::class], true)
            || ! ctype_digit($id) || ! ctype_digit($ventana)
            || ! hash_equals($this->firmar($tipo, (int) $id, (int) $ventana), $firma)) {
            throw $this->error('El código no es un QR de asistencia válido.');
        }

        $actual = intdiv(($ahora ?? now())->getTimestamp(), $this->segundos());

        if ((int) $ventana > $actual || $actual - (int) $ventana >= (int) config('asistencia.qr_dinamico.ventanas_validas')) {
            throw $this->error('El código QR caducó. Escanea el que se muestra ahora.');
        }

        return $clase::find((int) $id) ?? throw $this->error('La sesión del código no existe.');
    }

    private function firmar(string $tipo, int $id, int $ventana): string
    {
        return substr(hash_hmac('sha256', "{$tipo}:{$id}:{$ventana}", (string) config('app.key')), 0, 20);
    }

    private function segundos(): int
    {
        return max(5, (int) config('asistencia.qr_dinamico.segundos'));
    }

    private function error(string $mensaje): ValidationException
    {
        return ValidationException::withMessages(['codigo' => $mensaje]);
    }
}
