<?php

namespace App\Actions;

use App\Models\Proyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Acta del proyecto en PDF: datos del proyecto, asistencia de cada
 * integrante a las tutorías, su veredicto y espacio para las firmas de los
 * tres tutores.
 */
class GenerarActaProyecto
{
    public function __construct(private CalcularInasistencias $calcular) {}

    public function handle(Proyecto $proyecto): StreamedResponse
    {
        $proyecto->loadMissing(['pnf', 'curso.unidadCurricular', 'curso.seccion.periodo', 'curso.seccion.trayecto', 'curso.docentes', 'tutorTecnico', 'integrantes.estudiante']);

        $pdf = Pdf::loadView('pdf.acta-proyecto', [
            'proyecto' => $proyecto,
            'resumenes' => $this->calcular->deProyecto($proyecto),
            'integrantes' => $proyecto->integrantes->sortBy('estudiante.apellidos')->values(),
            'tutoriasRealizadas' => $proyecto->tutorias()->realizadas()->count(),
        ])
            ->setPaper('a4')
            ->output();

        return response()->streamDownload(
            fn () => print ($pdf),
            Str::slug("acta proyecto {$proyecto->titulo}").'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
