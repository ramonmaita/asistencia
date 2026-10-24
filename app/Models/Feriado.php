<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fecha', 'descripcion'])]
class Feriado extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }
}
