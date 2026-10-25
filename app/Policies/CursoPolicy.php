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

    /**
     * El coordinador y los docentes del curso inscriben y retiran estudiantes
     * (el docente carga la lista que le da el sistema académico).
     */
    public function gestionarInscripcion(User $user, Model $curso): bool
    {
        return $this->view($user, $curso);
    }

    public function generarSesiones(User $user, Model $curso): bool
    {
        return $this->coordinaPnfDe($user, $curso);
    }
}
