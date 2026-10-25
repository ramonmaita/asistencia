<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta del proyecto</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1f2937; margin: 0; }
        h1 { font-size: 13pt; margin: 0 0 1mm; color: #084362; }
        .sub { color: #4b5563; margin-bottom: 5mm; }
        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 5mm; }
        table.datos td { padding: 1mm 3mm 1mm 0; vertical-align: top; }
        .etiqueta { color: #6b7280; width: 32mm; }
        table.integrantes { width: 100%; border-collapse: collapse; }
        table.integrantes th { background: #084362; color: #fff; text-align: left; padding: 1.5mm 2mm; font-size: 8pt; }
        table.integrantes td { border-bottom: 0.2mm solid #d1d5db; padding: 1.5mm 2mm; }
        .num { text-align: right; }
        .aprobado { color: #15803d; font-weight: bold; }
        .reprobado { color: #b91c1c; font-weight: bold; }
        .pendiente { color: #6b7280; }
        .firmas { width: 100%; margin-top: 22mm; border-collapse: collapse; }
        .firmas td { width: 33.3%; text-align: center; padding: 0 4mm; }
        .linea { border-top: 0.3mm solid #1f2937; padding-top: 1.5mm; }
        .pie { margin-top: 6mm; color: #6b7280; font-size: 7.5pt; }
    </style>
</head>
<body>
    <h1>Acta del proyecto · {{ $proyecto->titulo }}</h1>
    <div class="sub">{{ $proyecto->pnf->nombre }} · {{ $proyecto->curso->unidadCurricular->nombre }}</div>

    <table class="datos">
        <tr>
            <td class="etiqueta">Sección</td>
            <td>{{ $proyecto->curso->seccion->codigo }} ({{ $proyecto->curso->seccion->trayecto->nombre }}) · Periodo {{ $proyecto->curso->seccion->periodo->nombre }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Comunidad o institución</td>
            <td>{{ $proyecto->comunidad ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Tutor académico</td>
            <td>{{ $proyecto->curso->docentes->pluck('name')->implode(', ') ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Tutor técnico</td>
            <td>{{ $proyecto->tutorTecnico?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Tutor institucional</td>
            <td>{{ $proyecto->tutor_institucional ?: '—' }}{{ $proyecto->tutor_institucional_contacto ? ' · '.$proyecto->tutor_institucional_contacto : '' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Tutorías realizadas</td>
            <td>{{ $tutoriasRealizadas }} · Límite de inasistencia: {{ number_format((float) $proyecto->curso->seccion->trayecto->umbral_inasistencia_tutorias, 2, ',', '.') }} %</td>
        </tr>
    </table>

    <table class="integrantes">
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Integrante</th>
                <th class="num">Tutorías</th>
                <th class="num">Inasistencias</th>
                <th class="num">% inasistencia</th>
                <th>Veredicto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($integrantes as $integrante)
                @php($resumen = $resumenes->get($integrante->estudiante_id))
                <tr>
                    <td>{{ $integrante->estudiante->cedula }}</td>
                    <td>{{ $integrante->estudiante->nombre_completo }}</td>
                    <td class="num">{{ $resumen->sesiones }}</td>
                    <td class="num">{{ $resumen->ausentes }}</td>
                    <td class="num">{{ $resumen->porcentajeFormateado() }}</td>
                    <td class="{{ $integrante->veredicto?->value ?? 'pendiente' }}">{{ $integrante->veredicto?->getLabel() ?? 'Pendiente' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">El proyecto no tiene integrantes.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="firmas">
        <tr>
            <td><div class="linea">Tutor académico</div></td>
            <td><div class="linea">Tutor técnico</div></td>
            <td><div class="linea">Tutor institucional</div></td>
        </tr>
    </table>

    <div class="pie">Generado el {{ now()->format('d/m/Y H:i') }}. La inasistencia a tutorías se controla aparte de la de clases.</div>
</body>
</html>
