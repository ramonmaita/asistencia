<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registro de asistencia por QR
    |--------------------------------------------------------------------------
    |
    | qr_dinamico.segundos: cada cuánto cambia el código que muestra el docente.
    | qr_dinamico.ventanas_validas: cuántos códigos seguidos se aceptan (el
    | actual y los anteriores), para tolerar la demora al escanear.
    |
    | ventana: el registro por QR solo se acepta el día de la sesión, desde
    | "minutos_antes" del inicio hasta la hora de fin. Pasados
    | "tolerancia_tardanza" minutos del inicio, queda como tardanza.
    |
    | gps.radio_metros: distancia máxima a la sede cuando la sede no define su
    | propio radio. Si la sede no tiene coordenadas, no se valida la ubicación.
    |
    */

    'qr_dinamico' => [
        'segundos' => (int) env('ASISTENCIA_QR_SEGUNDOS', 30),
        'ventanas_validas' => (int) env('ASISTENCIA_QR_VENTANAS', 2),
    ],

    'ventana' => [
        'minutos_antes' => (int) env('ASISTENCIA_MINUTOS_ANTES', 15),
        'tolerancia_tardanza' => (int) env('ASISTENCIA_TOLERANCIA_TARDANZA', 15),
    ],

    'gps' => [
        'radio_metros' => (int) env('ASISTENCIA_RADIO_METROS', 200),
    ],

];
