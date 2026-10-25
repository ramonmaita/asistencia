<?php

namespace App\Support;

/**
 * Paletas de la identidad visual para los paneles de Filament. Deben
 * coincidir con las variables de resources/css/filament/marca.css.
 *
 * Se dan los tonos completos porque, a partir de un solo azul tan oscuro,
 * Filament genera botones demasiado claros.
 */
final class Marca
{
    /** Azul de la UPT (la "P" del logo), para los paneles de administración y académico. */
    public const MARINO = [
        50 => '#eef0fd',
        100 => '#dde1fb',
        200 => '#bcc3f6',
        300 => '#909bef',
        400 => '#606ee5',
        500 => '#3d4bd8',
        600 => '#2433b5',
        700 => '#1b2896',
        800 => '#141f86',
        900 => '#0f1870',
        950 => '#0b1260',
    ];

    /** Azul petróleo (las franjas del logo), para el panel del estudiante. */
    public const TURQUESA = [
        50 => '#e9f6fb',
        100 => '#cbeaf5',
        200 => '#99d4ea',
        300 => '#5fb8da',
        400 => '#2f9cc4',
        500 => '#1a86ad',
        600 => '#13799f',
        700 => '#11607f',
        800 => '#114f68',
        900 => '#103f53',
        950 => '#082736',
    ];
}
