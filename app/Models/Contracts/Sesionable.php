<?php

namespace App\Models\Contracts;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Algo a lo que se le toma asistencia: una sesión de clase o una tutoría
 * del proyecto.
 *
 * @property int $pnf_id
 * @property Carbon $fecha
 */
interface Sesionable
{
    public function asistencias(): MorphMany;

    /**
     * Estudiantes convocados: los inscritos del curso o los integrantes del proyecto.
     */
    public function participantes(): BelongsToMany;

    /**
     * Docentes a cargo, a quienes se avisa de las justificaciones.
     *
     * @return Collection<int, User>
     */
    public function responsables(): Collection;

    /**
     * Indica si el usuario puede registrar asistencia y revisar justificaciones aquí.
     */
    public function esGestionadaPor(User $user): bool;

    /**
     * Texto corto para listados y notificaciones (p. ej. "Matemática · Lunes 12/10/2026 08:00–10:15").
     */
    public function descripcion(): string;

    /**
     * Rango horario sin segundos, p. ej. "08:00–10:15".
     */
    public function horario(): string;

    /**
     * Fecha con el día de la semana, p. ej. "Lunes 12/10/2026".
     */
    public function fechaLarga(): string;

    public function yaOcurrio(): bool;

    /**
     * Fecha y hora de inicio de la sesión.
     */
    public function inicio(): Carbon;

    /**
     * Fecha y hora de fin de la sesión.
     */
    public function fin(): Carbon;

    /**
     * Sede donde ocurre, para validar la ubicación del QR dinámico (null si no aplica).
     */
    public function ubicacion(): ?Sede;

    public function marcarRealizada(): void;
}
