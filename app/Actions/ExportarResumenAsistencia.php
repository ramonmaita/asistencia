<?php

namespace App\Actions;

use App\Models\Curso;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exporta el resumen de asistencia por estudiante de un curso a Excel o PDF.
 */
class ExportarResumenAsistencia
{
    public const ENCABEZADOS = ['Cédula', 'Apellidos', 'Nombres', 'Clases registradas', 'Asistencias', 'Tardanzas', 'Inasistencias', 'Justificadas', '% inasistencia', 'Estado'];

    public function __construct(
        private CalcularInasistencias $calcular,
        private ExportarHojaDeCalculo $hojaDeCalculo,
    ) {}

    public function excel(Curso $curso): StreamedResponse
    {
        $filas = $this->calcular->handle($curso)->map(fn (ResumenInasistencia $resumen): array => [
            $resumen->estudiante->cedula,
            $resumen->estudiante->apellidos,
            $resumen->estudiante->nombres,
            $resumen->sesiones,
            $resumen->asistencias(),
            $resumen->tardanzas,
            $resumen->ausentes,
            $resumen->justificadas,
            $resumen->porcentaje,
            $resumen->nivel->getLabel(),
        ]);

        return $this->hojaDeCalculo->handle(self::ENCABEZADOS, $filas, $this->nombreArchivo($curso, 'xlsx'));
    }

    public function pdf(Curso $curso): StreamedResponse
    {
        $curso->loadMissing(['pnf', 'unidadCurricular', 'seccion.periodo', 'seccion.trayecto', 'docentes']);

        $pdf = Pdf::loadView('pdf.resumen-asistencia', [
            'curso' => $curso,
            'resumenes' => $this->calcular->handle($curso),
            'sesionesRealizadas' => $curso->sesiones()->realizadas()->count(),
        ])
            ->setPaper('a4', 'landscape')
            ->output();

        return response()->streamDownload(fn () => print ($pdf), $this->nombreArchivo($curso, 'pdf'), ['Content-Type' => 'application/pdf']);
    }

    private function nombreArchivo(Curso $curso, string $extension): string
    {
        $curso->loadMissing(['unidadCurricular', 'seccion']);

        return Str::slug("asistencia {$curso->unidadCurricular->codigo} {$curso->seccion->codigo}").".{$extension}";
    }
}
