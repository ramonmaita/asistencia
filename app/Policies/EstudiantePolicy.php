<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Model;

/**
 * Los estudiantes los gestionan el coordinador y el administrativo del PNF.
 * En el panel de administración (sin PNF actual) el superadmin gestiona
 * los de todos los PNF.
 */
class EstudiantePolicy extends GestionadaPorCoordinador
{
    public function create(User $user): bool
    {
        return $user->administraPnfActual();
    }

    public function update(User $user, Model $estudiante): bool
    {
        return $this->gestiona($user, $estudiante);
    }

    public function view(User $user, Model $estudiante): bool
    {
        return $this->gestiona($user, $estudiante);
    }

    public function delete(User $user, Model $estudiante): bool
    {
        return $this->gestiona($user, $estudiante);
    }

    public function deleteAny(User $user): bool
    {
        return $user->administraPnfActual();
    }

    private function gestiona(User $user, Model $estudiante): bool
    {
        if (! app(PnfActual::class)->existe()) {
            return $user->is_superadmin;
        }

        return $this->administraPnfDe($user, $estudiante);
    }
}
