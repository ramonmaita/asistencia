<?php

namespace App\Policies;

use App\Enums\EstadoSesion;
use App\Enums\RolPnf;
use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Model;

/**
 * Las sesiones las gestionan el coordinador y los docentes del curso.
 */
class SesionClasePolicy extends GestionadaPorCoordinador
{
    public function viewAny(User $user): bool
    {
        return $this->perteneceAlPnfActual($user);
    }

    public function view(User $user, Model $sesion): bool
    {
        return $this->gestiona($user, $sesion);
    }

    /**
     * El administrativo no dicta clases, así que no crea sesiones.
     */
    public function create(User $user): bool
    {
        return $this->perteneceAlPnfActual($user) && $user->rolEnPnfActual() !== RolPnf::Administrativo;
    }

    public function update(User $user, Model $sesion): bool
    {
        return $this->gestiona($user, $sesion);
    }

    /**
     * Solo el coordinador elimina, y nunca una sesión con asistencias.
     */
    public function delete(User $user, Model $sesion): bool
    {
        return parent::delete($user, $sesion) && ! $sesion->asistencias()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * No se registra asistencia de una sesión futura ni de una suspendida.
     */
    public function registrarAsistencia(User $user, Model $sesion): bool
    {
        return $this->gestiona($user, $sesion)
            && $sesion->yaOcurrio()
            && $sesion->estado !== EstadoSesion::Suspendida;
    }

    private function gestiona(User $user, Model $sesion): bool
    {
        return $sesion->pnf_id === app(PnfActual::class)->id() && $sesion->esGestionadaPor($user);
    }

    private function perteneceAlPnfActual(User $user): bool
    {
        return $user->coordinaPnfActual() || $user->pnfs->contains('id', app(PnfActual::class)->id());
    }
}
