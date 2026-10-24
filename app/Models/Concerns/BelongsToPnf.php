<?php

namespace App\Models\Concerns;

use App\Models\Pnf;
use App\Models\Scopes\PnfScope;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aísla el modelo por PNF: filtra las consultas por el PNF actual y asigna
 * pnf_id automáticamente al crear. Todo modelo de negocio debe usarlo.
 */
trait BelongsToPnf
{
    public static function bootBelongsToPnf(): void
    {
        static::addGlobalScope(new PnfScope);

        static::creating(function ($model) {
            $pnfActual = app(PnfActual::class);

            if (blank($model->pnf_id) && $pnfActual->existe()) {
                $model->pnf_id = $pnfActual->id();
            }
        });
    }

    public function pnf(): BelongsTo
    {
        return $this->belongsTo(Pnf::class);
    }
}
