<?php

namespace App\Http\Controllers\Api\V1\Estudiante;

use App\Actions\RegistrarAsistenciaPorQr;
use App\Enums\MetodoAsistencia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegistrarPorQrDinamicoRequest;
use App\Http\Resources\V1\Enumerado;
use App\Support\CodigoQrDinamico;
use Illuminate\Http\JsonResponse;

class AsistenciaQrController extends Controller
{
    /**
     * El estudiante escanea el QR dinámico que muestra el docente.
     */
    public function store(RegistrarPorQrDinamicoRequest $request, CodigoQrDinamico $codigos, RegistrarAsistenciaPorQr $registrar): JsonResponse
    {
        $sesion = $codigos->resolver($request->string('codigo'));

        $resultado = $registrar->handle(
            $sesion,
            $request->user(),
            MetodoAsistencia::QrDinamico,
            latitud: $request->filled('latitud') ? (float) $request->input('latitud') : null,
            longitud: $request->filled('longitud') ? (float) $request->input('longitud') : null,
        );

        return response()->json([
            'message' => $resultado['nueva'] ? 'Asistencia registrada.' : 'Ya tenías tu asistencia registrada en esta sesión.',
            'sesion' => $sesion->descripcion(),
            'estado' => Enumerado::de($resultado['asistencia']->estado),
        ], $resultado['nueva'] ? 201 : 200);
    }
}
