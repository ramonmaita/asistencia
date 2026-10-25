<?php

namespace App\Filament\App\Resources\Proyectos\Pages;

use App\Filament\App\Concerns\RegistraAsistencia;
use App\Filament\App\Resources\Proyectos\ProyectoResource;
use App\Models\Contracts\Sesionable;
use App\Models\Proyecto;
use App\Models\Tutoria;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Locked;

/**
 * Asistencia de los integrantes a una tutoría del proyecto.
 */
class RegistrarAsistenciaTutoria extends Page
{
    use InteractsWithRecord;
    use RegistraAsistencia;

    protected static string $resource = ProyectoResource::class;

    protected static ?string $breadcrumb = 'Tutoría';

    #[Locked]
    public Tutoria $sesionTutoria;

    public function mount(int|string $record, int|string $tutoria): void
    {
        $this->record = $this->resolveRecord($record);
        $this->sesionTutoria = $this->getProyecto()->tutorias()->findOrFail($tutoria);

        $this->llenarFormulario();
    }

    public function getTitle(): string
    {
        return 'Tutoría · '.$this->sesionTutoria->tema;
    }

    public function getSubheading(): string
    {
        return "{$this->getProyecto()->titulo} · {$this->sesionTutoria->fechaLarga()} · {$this->sesionTutoria->horario()}";
    }

    protected function sesionable(): Sesionable
    {
        return $this->sesionTutoria;
    }

    protected function urlDeRegreso(): string
    {
        return ProyectoResource::getUrl('view', ['record' => $this->getProyecto()]);
    }

    private function getProyecto(): Proyecto
    {
        return $this->getRecord();
    }
}
