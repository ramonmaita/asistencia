<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen de asistencia</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1f2937; margin: 0; }
        h1 { font-size: 13pt; margin: 0 0 1mm; color: #1e3a8a; }
        .sub { color: #4b5563; margin-bottom: 4mm; }
        table.datos { border-collapse: collapse; margin-bottom: 4mm; }
        table.datos td { padding: 0.6mm 4mm 0.6mm 0; }
        .etiqueta { color: #6b7280; }
        table.resumen { width: 100%; border-collapse: collapse; }
        table.resumen th { background: #1e3a8a; color: #fff; text-align: left; padding: 1.5mm 2mm; font-size: 8pt; }
        table.resumen td { border-bottom: 0.2mm solid #d1d5db; padding: 1.2mm 2mm; }
        table.resumen tr:nth-child(even) td { background: #f3f4f6; }
        .num { text-align: right; }
        .normal { color: #15803d; }
        .en_riesgo { color: #b45309; font-weight: bold; }
        .superado { color: #b91c1c; font-weight: bold; }
        .pie { margin-top: 4mm; color: #6b7280; font-size: 7pt; }
    </style>
</head>
<body>
    <h1>Resumen de asistencia · {{ $curso->unidadCurricular->nombre }}</h1>
    <div class="sub">{{ $curso->pnf->nombre }}</div>

    <table class="datos">
        <tr>
            <td><span class="etiqueta">Sección:</span> {{ $curso->seccion->codigo }} ({{ $curso->seccion->trayecto->nombre }})</td>
            <td><span class="etiqueta">Periodo:</span> {{ $curso->seccion->periodo->nombre }}</td>
            <td><span class="etiqueta">Docentes:</span> {{ $curso->docentes->pluck('name')->implode(', ') ?: 'Sin asignar' }}</td>
        </tr>
        <tr>
            <td><span class="etiqueta">Clases realizadas:</span> {{ $sesionesRealizadas }}</td>
            <td><span class="etiqueta">Límite de inasistencia:</span> {{ number_format((float) $curso->pnf->umbral_inasistencia_clases, 2, ',', '.') }} %</td>
            <td><span class="etiqueta">Generado:</span> {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="resumen">
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Estudiante</th>
                <th class="num">Clases</th>
                <th class="num">Asistencias</th>
                <th class="num">Tardanzas</th>
                <th class="num">Inasistencias</th>
                <th class="num">Justificadas</th>
                <th class="num">% inasistencia</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resumenes as $resumen)
                <tr>
                    <td>{{ $resumen->estudiante->cedula }}</td>
                    <td>{{ $resumen->estudiante->nombre_completo }}</td>
                    <td class="num">{{ $resumen->sesiones }}</td>
                    <td class="num">{{ $resumen->asistencias() }}</td>
                    <td class="num">{{ $resumen->tardanzas }}</td>
                    <td class="num">{{ $resumen->ausentes }}</td>
                    <td class="num">{{ $resumen->justificadas }}</td>
                    <td class="num">{{ $resumen->porcentajeFormateado() }}</td>
                    <td class="{{ $resumen->nivel->value }}">{{ $resumen->nivel->getLabel() }}</td>
                </tr>
            @empty
                <tr><td colspan="9">La sección no tiene estudiantes inscritos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">El porcentaje considera solo las clases realizadas con asistencia registrada; las inasistencias justificadas no cuentan.</div>
</body>
</html>
