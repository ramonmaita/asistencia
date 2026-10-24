<?php

namespace App\Http\Middleware;

use App\Filament\Estudiante\Pages\CambiarContrasena;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mientras el estudiante conserve la contraseña inicial, solo puede ver la
 * página para cambiarla (y cerrar sesión).
 */
class ExigirCambioDeContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        $estudiante = auth('estudiante')->user();

        if ($estudiante?->debe_cambiar_password
            && ! $request->routeIs(CambiarContrasena::getRouteName(), 'filament.estudiante.auth.logout')) {
            return redirect(CambiarContrasena::getUrl());
        }

        return $next($request);
    }
}
