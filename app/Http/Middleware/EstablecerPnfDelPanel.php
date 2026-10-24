<?php

namespace App\Http\Middleware;

use App\Support\PnfActual;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel académico: el PNF actual es el tenant seleccionado en Filament.
 */
class EstablecerPnfDelPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        app(PnfActual::class)->establecer(Filament::getTenant());

        return $next($request);
    }
}
