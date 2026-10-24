<?php

namespace App\Actions;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga una tabla como archivo Excel (.xlsx) con encabezado en negrita.
 */
class ExportarHojaDeCalculo
{
    /**
     * @param  array<int, string>  $encabezados
     * @param  iterable<int, array<int, string|int|float|null>>  $filas
     */
    public function handle(array $encabezados, iterable $filas, string $nombreArchivo): StreamedResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'gpnf');

        $hoja = new Writer;
        $hoja->openToFile($ruta);
        $hoja->addRow(Row::fromValues($encabezados, (new Style)->setFontBold()));

        foreach ($filas as $fila) {
            $hoja->addRow(Row::fromValues($fila));
        }

        $hoja->close();

        return response()->streamDownload(function () use ($ruta): void {
            readfile($ruta);
            unlink($ruta);
        }, $nombreArchivo, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
