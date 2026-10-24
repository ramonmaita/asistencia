<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TrayectoPolicy extends GestionadaPorCoordinador
{
    /**
     * No se elimina un trayecto que ya tiene unidades curriculares o secciones.
     */
    public function delete(User $user, Model $trayecto): bool
    {
        return parent::delete($user, $trayecto)
            && ! $trayecto->unidadesCurriculares()->exists()
            && ! $trayecto->secciones()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
