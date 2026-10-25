<?php

namespace App\Http\Controllers\Api\V1\Estudiante;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CambiarContrasenaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ContrasenaController extends Controller
{
    /**
     * Cambia la contraseña (obligatorio mientras conserve la inicial).
     */
    public function update(CambiarContrasenaRequest $request): JsonResponse
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated('contrasena')),
            'debe_cambiar_password' => false,
        ])->save();

        return response()->json(['message' => 'Contraseña actualizada.']);
    }
}
