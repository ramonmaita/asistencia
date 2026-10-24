<?php

namespace App\Actions;

use App\Models\Estudiante;
use App\Models\Periodo;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Collection;

/**
 * Genera un PDF (A4) con los carnets de los estudiantes, diez por página.
 * El QR contiene el token del estudiante, que la app móvil escaneará para
 * registrar la asistencia.
 */
class GenerarCarnets
{
    /**
     * @param  Collection<int, Estudiante>  $estudiantes
     */
    public function handle(Collection $estudiantes): string
    {
        $generadorQr = new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => true,
            'scale' => 6,
            'quietzoneSize' => 1,
        ]));

        $carnets = $estudiantes
            ->loadMissing(['pnf', 'secciones.periodo'])
            ->sortBy(['apellidos', 'nombres'])
            ->map(fn (Estudiante $estudiante): array => [
                'estudiante' => $estudiante,
                'seccion' => $estudiante->secciones->firstWhere('periodo.activo', true)?->codigo,
                'qr' => $generadorQr->render($estudiante->contenidoQr()),
            ])
            ->values();

        return Pdf::loadView('pdf.carnets', [
            'paginas' => $carnets->chunk(10),
            'periodo' => Periodo::activo()->value('nombre'),
        ])
            ->setPaper('a4')
            ->output();
    }
}
