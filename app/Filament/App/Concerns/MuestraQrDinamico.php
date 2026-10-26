<?php

namespace App\Filament\App\Concerns;

use App\Actions\CerrarAsistencia;
use App\Actions\RegistrarAsistenciaPorQr;
use App\Enums\MetodoAsistencia;
use App\Models\Asistencia;
use App\Models\Contracts\Sesionable;
use App\Models\Estudiante;
use App\Support\CodigoQrDinamico;
use App\Support\VentanaDeAsistencia;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Página de la toma por QR de una sesión: proyecta en el aula el QR
 * dinámico o lee con la cámara los carnets de los estudiantes. Se refresca
 * sola (wire:poll) para mostrar el código vigente y quiénes se van
 * registrando; al cerrar, los que faltan quedan ausentes.
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

    /**
     * El docente lee con la cámara el QR del carnet (impreso o virtual) de
     * un estudiante; responde al lector con el resultado.
     *
     * @return array{exito: bool, mensaje: string}
     */
    public function registrarCarnet(string $contenido): array
    {
        $estudiante = Estudiante::delCarnet($contenido);

        if (! $estudiante) {
            return ['exito' => false, 'mensaje' => 'El código no es un carnet válido de este PNF.'];
        }

        try {
            $resultado = app(RegistrarAsistenciaPorQr::class)->handle($this->sesionable(), $estudiante, MetodoAsistencia::QrDocente, auth()->user());
        } catch (ValidationException $error) {
            return ['exito' => false, 'mensaje' => collect($error->errors())->flatten()->first()];
        }

        return [
            'exito' => true,
            'mensaje' => $resultado['nueva']
                ? "{$estudiante->nombre_completo}: {$resultado['asistencia']->estado->getLabel()}"
                : "{$estudiante->nombre_completo} ya estaba registrado",
        ];
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
