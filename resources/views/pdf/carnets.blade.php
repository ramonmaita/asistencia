<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Carnets</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1f2937; margin: 0; }
        table.hoja { border-collapse: separate; border-spacing: 4mm 3mm; width: 100%; }
        td.carnet {
            width: 85.6mm; height: 50mm; border: 0.3mm solid #9ca3af; border-radius: 3mm;
            padding: 0; vertical-align: top; overflow: hidden;
        }
        .cabecera { background: #1e3a8a; color: #fff; padding: 2mm 3mm; border-radius: 3mm 3mm 0 0; }
        .cabecera .pnf { font-size: 8.5pt; font-weight: bold; }
        .cabecera .sub { font-size: 6.5pt; opacity: .85; }
        table.cuerpo { width: 100%; border-collapse: collapse; }
        table.cuerpo td { vertical-align: top; padding: 2mm 3mm; }
        .nombre { font-size: 10pt; font-weight: bold; margin-bottom: 1.5mm; }
        .dato { margin-bottom: 1mm; }
        .etiqueta { color: #6b7280; font-size: 6.5pt; text-transform: uppercase; }
        .qr img { width: 28mm; height: 28mm; }
        .salto { page-break-after: always; }
    </style>
</head>
<body>
@foreach ($paginas as $pagina)
    <table class="hoja">
        @foreach ($pagina->chunk(2) as $fila)
            <tr>
                @foreach ($fila as $carnet)
                    <td class="carnet">
                        <div class="cabecera">
                            <div class="pnf">{{ $carnet['estudiante']->pnf->nombre }}</div>
                            <div class="sub">Carnet de estudiante{{ $periodo ? ' · Periodo '.$periodo : '' }}</div>
                        </div>
                        <table class="cuerpo">
                            <tr>
                                <td>
                                    <div class="nombre">{{ $carnet['estudiante']->nombres }}<br>{{ $carnet['estudiante']->apellidos }}</div>
                                    <div class="dato"><span class="etiqueta">Cédula</span><br>{{ number_format((int) $carnet['estudiante']->cedula, 0, ',', '.') }}</div>
                                    @if ($carnet['seccion'])
                                        <div class="dato"><span class="etiqueta">Sección</span><br>{{ $carnet['seccion'] }}</div>
                                    @endif
                                </td>
                                <td class="qr" style="width: 30mm; text-align: right;">
                                    <img src="{{ $carnet['qr'] }}" alt="QR">
                                </td>
                            </tr>
                        </table>
                    </td>
                @endforeach
                @if ($fila->count() === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
    @unless ($loop->last)
        <div class="salto"></div>
    @endunless
@endforeach
</body>
</html>
