<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Manual de usuario · Gestión PNF</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; line-height: 1.45; color: #1f2937; }
        .portada { text-align: center; padding-top: 70mm; }
        .portada img { width: 34mm; }
        .portada h1 { font-size: 22pt; color: #1e3a8a; margin: 8mm 0 2mm; }
        .portada p { color: #4b5563; margin: 1mm 0; }
        h1.guia { font-size: 16pt; color: #1e3a8a; border-bottom: 0.6mm solid #1e3a8a; padding-bottom: 2mm; }
        h2 { font-size: 12pt; color: #111827; margin: 6mm 0 2mm; }
        h3 { font-size: 10pt; margin: 4mm 0 1mm; }
        code { font-family: DejaVu Sans Mono, monospace; font-size: 8.5pt; background: #f3f4f6; }
        ul, ol { padding-left: 6mm; }
        li { margin-bottom: 1mm; }
        .salto { page-break-after: always; }
    </style>
</head>
<body>
    <div class="portada">
        <img src="{{ public_path('images/logo-upt.png') }}" alt="UPT Estado Bolívar">
        <h1>Manual de usuario</h1>
        <p>Sistema Gestión PNF · UPT Estado Bolívar</p>
        <p>{{ collect($guias)->pluck('titulo')->join(', ', ' y ') }}</p>
    </div>
    <div class="salto"></div>

    @foreach ($guias as $guia)
        <h1 class="guia">Guía: {{ $guia['titulo'] }}</h1>
        @foreach ($guia['secciones'] as $seccion)
            <h2>{{ $seccion['titulo'] }}</h2>
            {!! $seccion['html'] !!}
        @endforeach
        @unless ($loop->last)
            <div class="salto"></div>
        @endunless
    @endforeach
</body>
</html>
