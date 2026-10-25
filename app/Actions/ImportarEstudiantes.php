<?php

namespace App\Actions;

use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\Seccion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OpenSpout\Reader\CSV\Options as OpcionesCsv;
use OpenSpout\Reader\CSV\Reader as LectorCsv;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as LectorXlsx;

/**
 * Importa estudiantes de un archivo CSV o Excel (.xlsx) a un PNF y,
 * opcionalmente, los inscribe en una sección.
 *
 * Columnas reconocidas (la primera fila es el encabezado, sin importar
 * mayúsculas ni acentos): cedula, nombres, apellidos, email/correo, telefono.
 * Un estudiante que ya existe en el PNF se actualiza; uno que pertenece a
 * otro PNF se reporta como error. Opcionalmente, los inscritos en la sección
 * quedan inscritos también en todas sus unidades curriculares.
 */
class ImportarEstudiantes
{
    public const COLUMNAS = ['cedula', 'nombres', 'apellidos', 'email', 'telefono'];

    private const ALIAS = [
        'correo' => 'email',
        'correo_electronico' => 'email',
        'cedula_de_identidad' => 'cedula',
        'ci' => 'cedula',
        'nombre' => 'nombres',
        'apellido' => 'apellidos',
        'telefono_movil' => 'telefono',
        'celular' => 'telefono',
    ];

    /**
     * @param  bool  $inscribirEnCursos  Inscribe también en todas las unidades curriculares de la sección.
     */
    public function handle(string $rutaArchivo, Pnf $pnf, ?Seccion $seccion = null, bool $inscribirEnCursos = false): ResultadoImportacion
    {
        if ($seccion && $seccion->pnf_id !== $pnf->getKey()) {
            throw new InvalidArgumentException('La sección no pertenece al PNF.');
        }

        $resultado = new ResultadoImportacion;
        $encabezados = null;
        $cedulasVistas = [];

        foreach ($this->leerFilas($rutaArchivo) as $numeroFila => $celdas) {
            if ($encabezados === null) {
                $encabezados = array_map($this->normalizarEncabezado(...), $celdas);
                $faltantes = array_diff(['cedula', 'nombres', 'apellidos'], $encabezados);

                if ($faltantes !== []) {
                    $resultado->agregarError($numeroFila, null, 'Faltan columnas obligatorias: '.implode(', ', $faltantes).'.');

                    return $resultado;
                }

                continue;
            }

            $datos = $this->combinar($encabezados, $celdas);

            if (array_filter($datos) === []) {
                continue;
            }

            $this->importarFila($numeroFila, $datos, $pnf, $seccion, $inscribirEnCursos, $resultado, $cedulasVistas);
        }

        return $resultado;
    }

    /**
     * @param  array<string, ?string>  $datos
     * @param  array<string, true>  $cedulasVistas
     */
    private function importarFila(int $numeroFila, array $datos, Pnf $pnf, ?Seccion $seccion, bool $inscribirEnCursos, ResultadoImportacion $resultado, array &$cedulasVistas): void
    {
        $datos['cedula'] = $this->limpiarCedula($datos['cedula']);

        $validador = Validator::make($datos, [
            'cedula' => ['required', 'regex:/^\d{6,10}$/'],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ], [
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.regex' => 'La cédula debe tener solo números (6 a 10 dígitos).',
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'email.email' => 'El correo no es válido.',
        ]);

        if ($validador->fails()) {
            $resultado->agregarError($numeroFila, $datos['cedula'], $validador->errors()->first());

            return;
        }

        if (isset($cedulasVistas[$datos['cedula']])) {
            $resultado->agregarError($numeroFila, $datos['cedula'], 'La cédula está repetida en el archivo.');

            return;
        }

        $cedulasVistas[$datos['cedula']] = true;

        $estudiante = Estudiante::withoutGlobalScopes()->firstWhere('cedula', $datos['cedula']);

        if ($estudiante && $estudiante->pnf_id !== $pnf->getKey()) {
            $resultado->agregarError($numeroFila, $datos['cedula'], 'El estudiante ya está registrado en otro PNF.');

            return;
        }

        $atributos = array_filter([
            'nombres' => Str::title($datos['nombres']),
            'apellidos' => Str::title($datos['apellidos']),
            'email' => $datos['email'] ? Str::lower($datos['email']) : null,
            'telefono' => $datos['telefono'],
        ], fn (?string $valor): bool => filled($valor));

        if ($estudiante) {
            $estudiante->update($atributos);
            $resultado->actualizados++;
        } else {
            $estudiante = Estudiante::create([...$atributos, 'pnf_id' => $pnf->getKey(), 'cedula' => $datos['cedula']]);
            $resultado->creados++;
        }

        if ($seccion) {
            $resultado->inscritos += count($seccion->estudiantes()->syncWithoutDetaching([$estudiante->getKey()])['attached']);

            if ($inscribirEnCursos) {
                $seccion->inscribirEnSusCursos([$estudiante->getKey()]);
            }
        }
    }

    /**
     * @return iterable<int, array<int, ?string>>
     */
    private function leerFilas(string $rutaArchivo): iterable
    {
        $lector = $this->crearLector($rutaArchivo);
        $lector->open($rutaArchivo);

        try {
            foreach ($lector->getSheetIterator() as $hoja) {
                $numeroFila = 0;

                foreach ($hoja->getRowIterator() as $fila) {
                    $numeroFila++;

                    yield $numeroFila => array_map(
                        fn ($valor): ?string => $valor === null || $valor === '' ? null : trim((string) $valor),
                        $fila->toArray(),
                    );
                }

                break;
            }
        } finally {
            $lector->close();
        }
    }

    private function crearLector(string $rutaArchivo): ReaderInterface
    {
        if (str_starts_with((string) file_get_contents($rutaArchivo, length: 4), "PK\x03\x04")) {
            return new LectorXlsx;
        }

        $opciones = new OpcionesCsv;
        $primeraLinea = (string) strtok((string) file_get_contents($rutaArchivo, length: 4096), "\n");
        $opciones->FIELD_DELIMITER = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';

        return new LectorCsv($opciones);
    }

    private function normalizarEncabezado(?string $encabezado): string
    {
        $clave = Str::of((string) $encabezado)->ascii()->lower()->trim()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

        return self::ALIAS[$clave] ?? $clave;
    }

    /**
     * @param  array<int, string>  $encabezados
     * @param  array<int, ?string>  $celdas
     * @return array<string, ?string>
     */
    private function combinar(array $encabezados, array $celdas): array
    {
        $datos = array_fill_keys(self::COLUMNAS, null);

        foreach ($encabezados as $indice => $columna) {
            if (array_key_exists($columna, $datos)) {
                $datos[$columna] = $celdas[$indice] ?? null;
            }
        }

        return $datos;
    }

    private function limpiarCedula(?string $cedula): ?string
    {
        if ($cedula === null) {
            return null;
        }

        return preg_replace('/^[VEve]-?|[.\s]/', '', $cedula);
    }
}
