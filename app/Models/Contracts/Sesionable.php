<?php

namespace App\Models\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Algo a lo que se le toma asistencia: una sesión de clase y, desde la
 * Fase 4, una tutoría del proyecto.
 */
interface Sesionable
{
    public function asistencias(): MorphMany;

    /**
     * Indica si el usuario puede registrar asistencia y revisar justificaciones aquí.
     */
    public function esGestionadaPor(User $user): bool;

    /**
     * Texto corto para listados y notificaciones (p. ej. "Matemática · 12/10/2026").
     */
    public function descripcion(): string;
}
