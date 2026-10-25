<?php

namespace App\Http\Controllers\Api\V1\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarnetController extends Controller
{
    /**
     * Datos del carnet digital; la app dibuja el QR con "qr", que es el mismo
     * contenido del carnet impreso y el que escanea el docente.
     */
    public function show(Request $request): JsonResponse
    {
        $estudiante = $request->user()->load(['pnf', 'secciones.periodo']);

        return response()->json([
            'qr' => $estudiante->contenidoQr(),
            'cedula' => $estudiante->cedula,
            'nombres' => $estudiante->nombres,
            'apellidos' => $estudiante->apellidos,
            'pnf' => $estudiante->pnf->nombre,
            'secciones' => $estudiante->secciones->where('periodo.activo', true)->pluck('codigo')->values(),
            'periodo' => Periodo::activo()->value('nombre'),
        ]);
    }
}
