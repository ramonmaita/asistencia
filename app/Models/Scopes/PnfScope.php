<?php

namespace App\Models\Scopes;

use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PnfScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $pnfActual = app(PnfActual::class);

        if ($pnfActual->existe()) {
            $builder->where($model->qualifyColumn('pnf_id'), $pnfActual->id());
        }
    }
}
