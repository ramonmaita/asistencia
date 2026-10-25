<?php

namespace App\Http\Middleware\Api;

use App\Models\Estudiante;
use App\Support\PnfActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas del estudiante: el token debe ser de un estudiante activo. El PNF
 * actual es el suyo, y mientras conserve la contraseña inicial solo puede
 * cambiarla (igual que en el panel web).
 */
class SoloEstudiantes
{
    public function handle(Request $request, Closure $next): Response
    {
        $estudiante = $request->user();

        abort_unless($estudiante instanceof Estudiante && $estudiante->activo && $estudiante->tokenCan('estudiante'), 403, 'Esta sección es solo para estudiantes.');

        app(PnfActual::class)->establecer($estudiante->pnf_id);

        abort_if(
            $estudiante->debe_cambiar_password && ! $request->routeIs('api.v1.estudiante.contrasena'),
            403,
            'Debes cambiar tu contraseña inicial antes de continuar.',
        );

        return $next($request);
    }
}
