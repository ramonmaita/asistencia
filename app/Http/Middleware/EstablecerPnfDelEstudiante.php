<?php

namespace App\Http\Middleware;

use App\Support\PnfActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel del estudiante: el PNF actual es siempre el del estudiante autenticado.
 */
class EstablecerPnfDelEstudiante
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($estudiante = auth('estudiante')->user()) {
            app(PnfActual::class)->establecer($estudiante->pnf_id);
        }

        return $next($request);
    }
}
