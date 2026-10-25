<?php

namespace App\Filament\App\Resources\Cursos\Pages;

use App\Filament\App\Concerns\MuestraQrDinamico;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Models\Contracts\Sesionable;
use App\Models\Curso;
use App\Models\SesionClase;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Locked;

/**
 * QR dinámico de una sesión de clase para proyectarlo en el aula.
 */
class QrDinamicoSesion extends Page
{
    use InteractsWithRecord;
    use MuestraQrDinamico;

    protected static string $resource = CursoResource::class;

    protected static ?string $breadcrumb = 'QR dinámico';

    #[Locked]
    public SesionClase $sesionClase;

    public function mount(int|string $record, int|string $sesion): void
    {
        $this->record = $this->resolveRecord($record);
        $this->sesionClase = $this->getCurso()->sesiones()->findOrFail($sesion);

        $this->authorizeAccess();
    }

    public function getTitle(): string
    {
        return 'Asistencia por QR · '.$this->getCurso()->unidadCurricular->nombre;
    }

    public function getSubheading(): string
    {
        return "{$this->getCurso()->seccion->codigo} · {$this->sesionClase->fechaLarga()} · {$this->sesionClase->horario()}";
    }

    protected function sesionable(): Sesionable
    {
        return $this->sesionClase;
    }

    protected function urlDeRegreso(): string
    {
        return CursoResource::getUrl('view', ['record' => $this->getCurso()]);
    }

    protected function urlDelRegistroManual(): string
    {
        return CursoResource::getUrl('asistencia', ['record' => $this->getCurso(), 'sesion' => $this->sesionClase]);
    }

    private function getCurso(): Curso
    {
        return $this->getRecord();
    }
}
