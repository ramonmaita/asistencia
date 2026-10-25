<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Model;

/**
 * El coordinador gestiona todos los proyectos del PNF; el docente de la UC
 * Proyecto (tutor académico) gestiona los de sus cursos.
 */
class ProyectoPolicy extends GestionadaPorCoordinador
{
    public function viewAny(User $user): bool
    {
        return $user->coordinaPnfActual() || $user->pnfs->contains('id', app(PnfActual::class)->id());
    }

    public function view(User $user, Model $proyecto): bool
    {
        return $this->tutora($user, $proyecto);
    }

    /**
     * El formulario solo ofrece los cursos de la UC Proyecto que el docente dicta.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $proyecto): bool
    {
        return $this->tutora($user, $proyecto);
    }

    /**
     * No se elimina un proyecto que ya tiene tutorías realizadas.
     */
    public function delete(User $user, Model $proyecto): bool
    {
        return $this->tutora($user, $proyecto) && ! $proyecto->tutorias()->realizadas()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Registrar el veredicto (aprobado/reprobado) de los integrantes.
     */
    public function registrarVeredicto(User $user, Model $proyecto): bool
    {
        return $this->tutora($user, $proyecto);
    }

    private function tutora(User $user, Model $proyecto): bool
    {
        return $proyecto->pnf_id === app(PnfActual::class)->id() && $proyecto->esTutoradoPor($user);
    }
}
