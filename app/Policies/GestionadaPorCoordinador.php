<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Model;

/**
 * Política base para los catálogos de un PNF: solo el coordinador del PNF
 * actual (o el superadmin) los ve y los modifica.
 */
abstract class GestionadaPorCoordinador
{
    public function viewAny(User $user): bool
    {
        return $user->coordinaPnfActual();
    }

    public function view(User $user, Model $modelo): bool
    {
        return $this->coordinaPnfDe($user, $modelo);
    }

    public function create(User $user): bool
    {
        return $user->coordinaPnfActual();
    }

    public function update(User $user, Model $modelo): bool
    {
        return $this->coordinaPnfDe($user, $modelo);
    }

    public function delete(User $user, Model $modelo): bool
    {
        return $this->coordinaPnfDe($user, $modelo);
    }

    public function deleteAny(User $user): bool
    {
        return $user->coordinaPnfActual();
    }

    protected function coordinaPnfDe(User $user, Model $modelo): bool
    {
        return $modelo->pnf_id === app(PnfActual::class)->id() && $user->coordinaPnfActual();
    }
}
