<?php

namespace App\Http\Middleware\Api;

use App\Models\Pnf;
use App\Support\PnfActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El PNF de la URL pasa a ser el PNF actual, si el docente pertenece a él
 * (si no, 404: no se revela que el PNF existe).
 */
class EstablecerPnfDelDocente
{
    public function handle(Request $request, Closure $next): Response
    {
        $pnf = $request->route('pnf');

        abort_unless($pnf instanceof Pnf && $pnf->activo && $request->user()->canAccessTenant($pnf), 404);

        app(PnfActual::class)->establecer($pnf);

        return $next($request);
    }
}
