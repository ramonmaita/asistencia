<?php

namespace App\Http\Resources\V1;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Estudiante
 */
class EstudianteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tipo' => 'estudiante',
            'id' => $this->id,
            'cedula' => $this->cedula,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'email' => $this->email,
            'pnf' => $this->whenLoaded('pnf', fn (): array => ['nombre' => $this->pnf->nombre, 'codigo' => $this->pnf->codigo]),
            'debe_cambiar_password' => $this->debe_cambiar_password,
        ];
    }
}
