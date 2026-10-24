<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Model;

/**
 * El coordinador gestiona los cursos; el docente ve los que dicta.
 */
class CursoPolicy extends GestionadaPorCoordinador
{
    public function viewAny(User $user): bool
    {
        return $user->coordinaPnfActual()
            || $user->pnfs->contains('id', app(PnfActual::class)->id());
    }

    public function view(User $user, Model $curso): bool
    {
        return $this->coordinaPnfDe($user, $curso)
            || ($curso->pnf_id === app(PnfActual::class)->id() && $curso->docentes()->whereKey($user->getKey())->exists());
    }
}
