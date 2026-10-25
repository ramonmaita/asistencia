<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Mismas reglas que la página "Cambiar contraseña" del panel del estudiante.
 */
class CambiarContrasenaRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'contrasena_actual' => ['required', 'string', 'current_password:sanctum'],
            'contrasena' => ['required', 'string', Password::min(8)->letters()->numbers(), 'different:contrasena_actual', 'not_in:'.$this->user()->cedula, 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contrasena_actual.current_password' => 'La contraseña actual no es correcta.',
            'contrasena.not_in' => 'La nueva contraseña no puede ser tu cédula.',
        ];
    }
}
