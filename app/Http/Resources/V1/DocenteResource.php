<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class DocenteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tipo' => 'docente',
            'id' => $this->id,
            'nombre' => $this->name,
            'cedula' => $this->cedula,
            'email' => $this->email,
        ];
    }
}
