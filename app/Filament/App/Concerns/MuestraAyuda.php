<?php

namespace App\Filament\App\Concerns;

use App\Support\ManualDeUsuario;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Página de ayuda de un panel: las guías del manual que corresponden al
 * usuario, con búsqueda, y su descarga en PDF.
 */
trait MuestraAyuda
{
    /**
     * Guías del manual (claves de ManualDeUsuario::GUIAS) que ve el usuario.
     *
     * @return list<string>
     */
    abstract public function guiasDelUsuario(): array;

    public function getView(): string
    {
        return 'filament.ayuda';
    }

    public function getSubheading(): string
    {
        return 'Guías paso a paso y preguntas frecuentes. Escribe en el buscador para encontrar un tema.';
    }

    /**
     * @return array<string, array{titulo: string, secciones: list<array{id: string, titulo: string, html: string, texto: string}>}>
     */
    public function manual(): array
    {
        return app(ManualDeUsuario::class)->guias($this->guiasDelUsuario());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarManual')
                ->label('Descargar manual (PDF)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (ManualDeUsuario $manual): StreamedResponse => response()->streamDownload(
                    fn () => print ($manual->pdf($this->guiasDelUsuario())),
                    'manual-gestion-pnf.pdf',
                    ['Content-Type' => 'application/pdf'],
                )),
        ];
    }
}
