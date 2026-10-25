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
    /** Azul marino institucional (paneles de administración y académico). */
    public const MARINO = [
        50 => '#eef3fa',
        100 => '#d9e3f2',
        200 => '#b7c9e6',
        300 => '#8aa7d4',
        400 => '#5a7fbd',
        500 => '#3a61a3',
        600 => '#2c5288',
        700 => '#234370',
        800 => '#1d3a66',
        900 => '#172f52',
        950 => '#0f1f38',
    ];

    /** Turquesa del panel del estudiante. */
    public const TURQUESA = [
        50 => '#e6f7f5',
        100 => '#c2ece7',
        200 => '#8fdcd3',
        300 => '#55c6ba',
        400 => '#26ad9f',
        500 => '#0f9b8e',
        600 => '#0c8276',
        700 => '#0d695f',
        800 => '#0f544d',
        900 => '#0f4540',
        950 => '#042a27',
    ];
}
