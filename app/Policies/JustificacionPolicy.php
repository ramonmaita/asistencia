<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Model;

/**
 * El personal no crea ni edita justificaciones (las solicita el estudiante):
 * el docente de la sesión o el coordinador las ven y las revisan; el
 * administrativo solo las consulta.
 */
class JustificacionPolicy extends GestionadaPorCoordinador
{
    public function viewAny(User $user): bool
    {
        return $user->coordinaPnfActual() || $user->pnfs->contains('id', app(PnfActual::class)->id());
    }

    public function view(User $user, Model $justificacion): bool
    {
        return $this->esDeSuSesion($user, $justificacion) || $this->administraPnfDe($user, $justificacion);
    }

    public function revisar(User $user, Model $justificacion): bool
    {
        return $this->esDeSuSesion($user, $justificacion) && $justificacion->estaPendiente();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $justificacion): bool
    {
        return false;
    }

    public function delete(User $user, Model $justificacion): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function esDeSuSesion(User $user, Model $justificacion): bool
    {
        return $justificacion->pnf_id === app(PnfActual::class)->id()
            && $justificacion->asistencia->sesionable->esGestionadaPor($user);
    }
}
