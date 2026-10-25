<?php

namespace App\Filament\App\Resources\Cursos\Pages;

use App\Filament\App\Concerns\RegistraAsistencia;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Models\Contracts\Sesionable;
use App\Models\Curso;
use App\Models\SesionClase;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Locked;

/**
 * Registro manual de la asistencia de una sesión de clase: lista a todos
 * los inscritos del curso (por defecto presentes) y al guardar marca la
 * sesión como realizada.
 */
class RegistrarAsistencia extends Page
{
    use InteractsWithRecord;
    use RegistraAsistencia;

    protected static string $resource = CursoResource::class;

    protected static ?string $breadcrumb = 'Asistencia';

    #[Locked]
    public SesionClase $sesionClase;

    public function mount(int|string $record, int|string $sesion): void
    {
        $this->record = $this->resolveRecord($record);
        $this->sesionClase = $this->getCurso()->sesiones()->findOrFail($sesion);

        $this->llenarFormulario();
    }

    public function getTitle(): string
    {
        return 'Asistencia · '.$this->getCurso()->unidadCurricular->nombre;
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

    private function getCurso(): Curso
    {
        return $this->getRecord();
    }
}
