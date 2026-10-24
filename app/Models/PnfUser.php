<?php

namespace App\Models;

use App\Enums\RolPnf;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PnfUser extends Pivot
{
    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'rol' => RolPnf::class,
        ];
    }
}
