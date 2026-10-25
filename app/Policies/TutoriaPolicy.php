<?php

namespace App\Policies;

use App\Enums\EstadoSesion;
use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Model;

/**
 * Las tutorías las gestionan los tutores académico y técnico del proyecto o el coordinador.
 */
class TutoriaPolicy extends GestionadaPorCoordinador
{
    public function viewAny(User $user): bool
    {
        return $user->coordinaPnfActual() || $user->pnfs->contains('id', app(PnfActual::class)->id());
    }

    public function view(User $user, Model $tutoria): bool
    {
        return $this->gestiona($user, $tutoria);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $tutoria): bool
    {
        return $this->gestiona($user, $tutoria);
    }

    /**
     * Solo se elimina una tutoría sin asistencias registradas.
     */
    public function delete(User $user, Model $tutoria): bool
    {
        return $this->gestiona($user, $tutoria) && ! $tutoria->asistencias()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * No se registra asistencia de una tutoría futura ni de una suspendida.
     */
    public function registrarAsistencia(User $user, Model $tutoria): bool
    {
        return $this->gestiona($user, $tutoria)
            && $tutoria->yaOcurrio()
            && $tutoria->estado !== EstadoSesion::Suspendida;
    }

    private function gestiona(User $user, Model $tutoria): bool
    {
        return $tutoria->pnf_id === app(PnfActual::class)->id() && $tutoria->esGestionadaPor($user);
    }
}
