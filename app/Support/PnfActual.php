<?php

namespace App\Support;

use App\Models\Pnf;

/**
 * Guarda el PNF (tenant) de la petición en curso.
 *
 * Lo establecen los middlewares de cada panel (y de la API en la Fase 5).
 * Mientras no haya PNF establecido el PnfScope no filtra, lo cual solo
 * ocurre en el panel de administración (superadmin), la consola y el login.
 */
class PnfActual
{
    private ?int $id = null;

    public function establecer(Pnf|int|null $pnf): void
    {
        $this->id = $pnf instanceof Pnf ? $pnf->getKey() : $pnf;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function existe(): bool
    {
        return $this->id !== null;
    }

    public function limpiar(): void
    {
        $this->id = null;
    }
}
