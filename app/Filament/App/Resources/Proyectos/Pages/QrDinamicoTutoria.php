<?php

namespace App\Filament\App\Resources\Proyectos\Pages;

use App\Filament\App\Concerns\MuestraQrDinamico;
use App\Filament\App\Resources\Proyectos\ProyectoResource;
use App\Models\Contracts\Sesionable;
use App\Models\Proyecto;
use App\Models\Tutoria;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Locked;

/**
 * QR dinámico de una tutoría para que los integrantes lo escaneen.
 */
class QrDinamicoTutoria extends Page
{
    use InteractsWithRecord;
    use MuestraQrDinamico;

    protected static string $resource = ProyectoResource::class;

    protected static ?string $breadcrumb = 'QR dinámico';

    #[Locked]
    public Tutoria $sesionTutoria;

    public function mount(int|string $record, int|string $tutoria): void
    {
        $this->record = $this->resolveRecord($record);
        $this->sesionTutoria = $this->getProyecto()->tutorias()->findOrFail($tutoria);

        $this->authorizeAccess();
    }

    public function getTitle(): string
    {
        return 'Tutoría por QR · '.$this->sesionTutoria->tema;
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

    protected function urlDelRegistroManual(): string
    {
        return ProyectoResource::getUrl('tutoria', ['record' => $this->getProyecto(), 'tutoria' => $this->sesionTutoria]);
    }

    private function getProyecto(): Proyecto
    {
        return $this->getRecord();
    }
}
