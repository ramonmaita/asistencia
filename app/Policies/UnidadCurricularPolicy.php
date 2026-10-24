<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UnidadCurricularPolicy extends GestionadaPorCoordinador
{
    /**
     * No se elimina una unidad curricular que ya se dicta en algún curso.
     */
    public function delete(User $user, Model $unidadCurricular): bool
    {
        return parent::delete($user, $unidadCurricular)
            && ! $unidadCurricular->cursos()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
