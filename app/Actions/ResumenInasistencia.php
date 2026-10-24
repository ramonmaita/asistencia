<?php

namespace App\Actions;

use App\Enums\NivelInasistencia;
use App\Models\Estudiante;

/**
 * Asistencia acumulada de un estudiante en un curso.
 */
final readonly class ResumenInasistencia
{
    public function __construct(
        public Estudiante $estudiante,
        public int $sesiones,
        public int $presentes,
        public int $tardanzas,
        public int $ausentes,
        public int $justificadas,
        public float $porcentaje,
        public NivelInasistencia $nivel,
    ) {}

    /**
     * Sesiones a las que asistió (presente o con tardanza).
     */
    public function asistencias(): int
    {
        return $this->presentes + $this->tardanzas;
    }

    public function porcentajeFormateado(): string
    {
        return number_format($this->porcentaje, 2, ',', '.').' %';
    }
}
