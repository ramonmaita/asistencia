<?php

namespace App\Filament\App\Concerns;

use App\Actions\CerrarAsistencia;
use App\Models\Asistencia;
use App\Models\Contracts\Sesionable;
use App\Support\CodigoQrDinamico;
use App\Support\VentanaDeAsistencia;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Página para proyectar en el aula el QR dinámico de una sesión. Se
 * refresca sola (wire:poll) para mostrar el código vigente y quiénes se
 * van registrando; al cerrar, los que faltan quedan ausentes.
 */
trait MuestraQrDinamico
{
    abstract protected function sesionable(): Sesionable;

    abstract protected function urlDeRegreso(): string;

    abstract protected function urlDelRegistroManual(): string;

    public function getView(): string
    {
        return 'filament.app.pages.qr-dinamico';
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('registrarAsistencia', $this->sesionable()), 403);
    }

    public function estaAbierta(): bool
    {
        return app(VentanaDeAsistencia::class)->estaAbierta($this->sesionable());
    }

    public function abre(): string
    {
        return app(VentanaDeAsistencia::class)->abre($this->sesionable())->format('h:i a');
    }

    /**
     * @return array{imagen: string, expira_at: string, segundos: int}
     */
    public function qr(): array
    {
        $codigo = app(CodigoQrDinamico::class)->generar($this->sesionable());

        $imagen = (new QRCode(new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => true,
            'svgAddXmlHeader' => false,
            'quietzoneSize' => 2,
        ])))->render($codigo['codigo']);

        return ['imagen' => $imagen, 'expira_at' => $codigo['expira_at']->toIso8601String(), 'segundos' => $codigo['segundos']];
    }

    public function convocados(): int
    {
        return $this->sesionable()->participantes()->count();
    }

    /**
     * @return Collection<int, Asistencia>
     */
    public function registrados(): Collection
    {
        return $this->sesionable()->asistencias()->with('estudiante')->latest('registrado_at')->latest('id')->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registroManual')
                ->label('Registro manual')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->url(fn (): string => $this->urlDelRegistroManual()),
            Action::make('cerrar')
                ->label('Cerrar asistencia')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Quienes no se hayan registrado quedarán ausentes. Puedes corregirlo después con el registro manual.')
                ->action(function (CerrarAsistencia $cerrar): void {
                    $ausentes = $cerrar->handle($this->sesionable(), auth()->user());

                    Notification::make()->title('Asistencia cerrada')->body("Ausentes: {$ausentes}.")->success()->send();

                    $this->redirect($this->urlDeRegreso());
                }),
        ];
    }
}
