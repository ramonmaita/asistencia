<?php

namespace App\Http\Resources\V1;

use App\Models\Pnf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pnf
 */
class PnfResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'rol' => Enumerado::de($request->user()->rolEn($this->resource)),
        ];
    }
}
