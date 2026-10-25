<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IniciarSesionDocenteRequest;
use App\Http\Requests\Api\V1\IniciarSesionEstudianteRequest;
use App\Http\Resources\V1\DocenteResource;
use App\Http\Resources\V1\EstudianteResource;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Inicio y cierre de sesión de la app. Cada dispositivo recibe su propio
 * token, que se revoca al cerrar sesión.
 */
class AutenticacionController extends Controller
{
    public function docente(IniciarSesionDocenteRequest $request): JsonResponse
    {
        $user = User::firstWhere('email', $request->string('email')->lower()->toString());

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw $this->credencialesIncorrectas('email');
        }

        if (! $user->activo || (! $user->is_superadmin && ! $user->pnfs()->exists())) {
            throw ValidationException::withMessages(['email' => 'Tu cuenta no tiene acceso a ningún PNF.']);
        }

        return response()->json([
            'token' => $user->createToken($request->string('dispositivo'), ['docente'])->plainTextToken,
            'usuario' => new DocenteResource($user),
        ], 201);
    }

    public function estudiante(IniciarSesionEstudianteRequest $request): JsonResponse
    {
        $estudiante = Estudiante::withoutGlobalScopes()->with('pnf')->firstWhere('cedula', $request->cedula());

        if (! $estudiante || ! Hash::check($request->string('password'), $estudiante->password)) {
            throw $this->credencialesIncorrectas('cedula');
        }

        if (! $estudiante->activo) {
            throw ValidationException::withMessages(['cedula' => 'Tu cuenta de estudiante está inactiva.']);
        }

        return response()->json([
            'token' => $estudiante->createToken($request->string('dispositivo'), ['estudiante'])->plainTextToken,
            'usuario' => new EstudianteResource($estudiante),
        ], 201);
    }

    public function yo(Request $request): JsonResource
    {
        $usuario = $request->user();

        return $usuario instanceof Estudiante
            ? new EstudianteResource($usuario->load('pnf'))
            : new DocenteResource($usuario);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    private function credencialesIncorrectas(string $campo): ValidationException
    {
        return ValidationException::withMessages([$campo => 'Las credenciales no son correctas.']);
    }
}
