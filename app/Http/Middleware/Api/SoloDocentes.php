<?php

namespace App\Http\Middleware\Api;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas del docente: el token debe ser de un usuario activo con la habilidad "docente".
 */
class SoloDocentes
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->activo && $user->tokenCan('docente'), 403, 'Esta sección es solo para docentes.');

        return $next($request);
    }
}
