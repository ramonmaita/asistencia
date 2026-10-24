<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre', 'direccion', 'activo'])]
class Sede extends Model
{
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
