<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Justificacion;
use Illuminate\Foundation\Http\FormRequest;

class SolicitarJustificacionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:1000'],
            'soporte' => ['required', 'file', 'mimetypes:'.implode(',', Justificacion::TIPOS_PERMITIDOS), 'max:'.Justificacion::TAMANO_MAXIMO],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'soporte.mimetypes' => 'El soporte debe ser un PDF o una imagen JPG o PNG.',
            'soporte.max' => 'El soporte no puede pesar más de 5 MB.',
        ];
    }
}
