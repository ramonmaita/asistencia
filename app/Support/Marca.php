<?php

namespace App\Support;

/**
 * Paletas de la identidad visual para los paneles de Filament. Deben
 * coincidir con las variables de resources/css/filament/marca.css.
 *
 * Se dan los tonos completos (en lugar de un solo color) para controlar el
 * contraste de los botones, que Filament calcula a partir del tono 600.
 */
final class Marca
{
    /**
     * Azules de las franjas del logo (la transición de la universidad); el
     * tono 600 es la franja central. Lo usan los tres paneles.
     */
    public const TRANSICION = [
        50 => '#ebf7fb',
        100 => '#d0ecf5',
        200 => '#a5d8eb',
        300 => '#6fbedd',
        400 => '#3ba3cc',
        500 => '#1a88b8',
        600 => '#0b7098',
        700 => '#0a5c7e',
        800 => '#084a66',
        900 => '#063b53',
        950 => '#022c3c',
    ];
}
