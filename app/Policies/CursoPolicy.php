<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Model;

/**
 * El coordinador gestiona los cursos; el administrativo los ve todos (e
 * inscribe estudiantes) y el docente ve los que dicta.
 */
class CursoPolicy extends GestionadaPorCoordinador
{
    public function viewAny(User $user): bool
    {
        return $user->administraPnfActual()
            || $user->pnfs->contains('id', app(PnfActual::class)->id());
    }

    public function view(User $user, Model $curso): bool
    {
        return $this->administraPnfDe($user, $curso)
            || ($curso->pnf_id === app(PnfActual::class)->id() && $curso->docentes()->whereKey($user->getKey())->exists());
    }

    /**
     * El coordinador, el administrativo y los docentes del curso inscriben y
     * retiran estudiantes (cargan la lista que da el sistema académico).
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
