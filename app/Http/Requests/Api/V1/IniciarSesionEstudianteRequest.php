<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IniciarSesionEstudianteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'cedula' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'dispositivo' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Acepta la cédula con prefijo o puntos (V-30.111.222).
     */
    public function cedula(): string
    {
        return preg_replace('/\D/', '', (string) $this->input('cedula'));
    }
}
