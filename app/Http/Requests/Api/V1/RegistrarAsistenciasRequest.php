<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EstadoAsistencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registro manual desde la app: {"estados": {"<id del estudiante>": "presente|ausente|tardanza|justificado"}}.
 */
class RegistrarAsistenciasRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'estados' => ['required', 'array', 'min:1'],
            'estados.*' => ['required', Rule::enum(EstadoAsistencia::class)],
        ];
    }

    /**
     * @return array<int, EstadoAsistencia>
     */
    public function estados(): array
    {
        return collect($this->validated('estados'))
            ->mapWithKeys(fn (string $estado, int|string $estudianteId): array => [(int) $estudianteId => EstadoAsistencia::from($estado)])
            ->all();
    }
}
