<?php

namespace App\Policies;

use App\Enums\RolPnf;
use App\Models\User;
use App\Support\PnfActual;

/**
 * Cuentas del personal (docentes, coordinadores y administrativos).
 *
 * En el panel de administración el superadmin gestiona todas. En el panel
 * de un PNF, el coordinador y el administrativo gestionan a su personal:
 * editan los datos solo de quien pertenece únicamente a ese PNF (los demás
 * los gestiona el superadmin) y nunca tocan a un superadmin.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->administraPnfActual();
    }

    public function view(User $user, User $miembro): bool
    {
        if (! app(PnfActual::class)->existe()) {
            return $user->is_superadmin;
        }

        return $user->administraPnfActual() && $this->esDelPnfActual($miembro);
    }

    public function create(User $user): bool
    {
        return $user->administraPnfActual();
    }

    public function update(User $user, User $miembro): bool
    {
        if (! app(PnfActual::class)->existe()) {
            return $user->is_superadmin;
        }

        return $this->asignarRol($user, $miembro) && $miembro->pnfs()->count() === 1;
    }

    /**
     * Cambiar el rol de alguien en el PNF actual o quitarlo del PNF. Nadie
     * cambia su propio rol (para no quedarse sin acceso) y solo un
     * coordinador cambia o quita a otro coordinador.
     */
    public function asignarRol(User $user, User $miembro): bool
    {
        return app(PnfActual::class)->existe()
            && $user->administraPnfActual()
            && $this->esDelPnfActual($miembro)
            && ! $miembro->is_superadmin
            && ! $miembro->is($user)
            && ($miembro->rolEnPnfActual() !== RolPnf::Coordinador || $user->coordinaPnfActual());
    }

    /**
     * Solo el superadmin elimina cuentas, desde el panel de administración.
     */
    public function delete(User $user, User $miembro): bool
    {
        return ! app(PnfActual::class)->existe() && $user->is_superadmin && ! $miembro->is($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function esDelPnfActual(User $miembro): bool
    {
        return $miembro->pnfs()->whereKey(app(PnfActual::class)->id())->exists();
    }
}
