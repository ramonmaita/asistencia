<?php

namespace App\Http\Controllers\Api\V1\Docente;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PnfResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PnfController extends Controller
{
    /**
     * PNF en los que trabaja el docente; el "slug" va en las demás rutas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return PnfResource::collection($request->user()->getTenants(filament()->getPanel('app')));
    }
}
