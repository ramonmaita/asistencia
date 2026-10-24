<?php

namespace Tests\Feature;

use App\Actions\ImportarEstudiantes;
use App\Actions\ResultadoImportacion;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\Trayecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportarEstudiantesTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_estudiantes_desde_csv_con_punto_y_coma_y_cedula_como_clave_inicial(): void
    {
        $pnf = Pnf::factory()->create();

        $resultado = $this->importar($pnf, "Cédula;Nombres;Apellidos;Correo\nV-30.111.222;maría josé;pérez;MARIA@EJEMPLO.COM\n");

        $this->assertSame(1, $resultado->creados);
        $this->assertSame([], $resultado->errores);

        $estudiante = Estudiante::withoutGlobalScopes()->firstWhere('cedula', '30111222');
        $this->assertSame($pnf->id, $estudiante->pnf_id);
        $this->assertSame('María José', $estudiante->nombres);
        $this->assertSame('maria@ejemplo.com', $estudiante->email);
        $this->assertTrue($estudiante->debe_cambiar_password);
        $this->assertTrue(Hash::check('30111222', $estudiante->password));
    }

    public function test_lee_archivos_excel(): void
    {
        $pnf = Pnf::factory()->create();
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');

        $escritor = new Writer;
        $escritor->openToFile($ruta);
        $escritor->addRow(Row::fromValues(['cedula', 'nombres', 'apellidos']));
        $escritor->addRow(Row::fromValues([28999888, 'Luis', 'Gómez']));
        $escritor->close();

        $resultado = app(ImportarEstudiantes::class)->handle($ruta, $pnf);
        unlink($ruta);

        $this->assertSame(1, $resultado->creados);
        $this->assertDatabaseHas('estudiantes', ['cedula' => '28999888', 'pnf_id' => $pnf->id]);
    }

    public function test_actualiza_al_estudiante_existente_del_mismo_pnf_sin_tocar_su_clave(): void
    {
        $pnf = Pnf::factory()->create();
        $existente = Estudiante::factory()->for($pnf)->create(['cedula' => '30111222', 'nombres' => 'Maria']);
        $claveAnterior = $existente->password;

        $resultado = $this->importar($pnf, "cedula,nombres,apellidos\n30111222,María,Pérez\n");

        $this->assertSame(0, $resultado->creados);
        $this->assertSame(1, $resultado->actualizados);
        $this->assertSame('María', $existente->fresh()->nombres);
        $this->assertSame($claveAnterior, $existente->fresh()->password);
    }

    public function test_rechaza_al_estudiante_que_pertenece_a_otro_pnf(): void
    {
        $pnf = Pnf::factory()->create();
        $ajeno = Estudiante::factory()->create(['cedula' => '30111222']);

        $resultado = $this->importar($pnf, "cedula,nombres,apellidos\n30111222,María,Pérez\n");

        $this->assertSame(0, $resultado->creados + $resultado->actualizados);
        $this->assertSame('El estudiante ya está registrado en otro PNF.', $resultado->errores[0]['mensaje']);
        $this->assertNotSame($pnf->id, $ajeno->fresh()->pnf_id);
    }

    public function test_reporta_filas_invalidas_e_importa_las_validas(): void
    {
        $pnf = Pnf::factory()->create();

        $resultado = $this->importar($pnf, implode("\n", [
            'cedula,nombres,apellidos',
            '12AB,Ana,Ruiz',
            '20111333,,Ruiz',
            '20111444,Pedro,Díaz',
            '20111444,Pedro,Díaz',
        ]));

        $this->assertSame(1, $resultado->creados);
        $this->assertSame([
            ['fila' => 2, 'cedula' => '12AB', 'mensaje' => 'La cédula debe tener solo números (6 a 10 dígitos).'],
            ['fila' => 3, 'cedula' => '20111333', 'mensaje' => 'Los nombres son obligatorios.'],
            ['fila' => 5, 'cedula' => '20111444', 'mensaje' => 'La cédula está repetida en el archivo.'],
        ], $resultado->errores);
    }

    public function test_rechaza_el_archivo_sin_columnas_obligatorias(): void
    {
        $pnf = Pnf::factory()->create();

        $resultado = $this->importar($pnf, "cedula,nombre completo\n30111222,María Pérez\n");

        $this->assertSame(0, $resultado->creados);
        $this->assertSame('Faltan columnas obligatorias: nombres, apellidos.', $resultado->errores[0]['mensaje']);
    }

    public function test_inscribe_en_la_seccion_indicada(): void
    {
        $pnf = Pnf::factory()->create();
        $seccion = Seccion::factory()->for(Trayecto::factory()->for($pnf))->create();
        $yaInscrito = Estudiante::factory()->for($pnf)->create(['cedula' => '30111222']);
        $seccion->estudiantes()->attach($yaInscrito);

        $resultado = $this->importar($pnf, "cedula,nombres,apellidos\n30111222,María,Pérez\n30111333,José,Ramos\n", $seccion);

        $this->assertSame(1, $resultado->inscritos);
        $this->assertSame(2, $seccion->estudiantes()->count());
    }

    private function importar(Pnf $pnf, string $contenido, ?Seccion $seccion = null): ResultadoImportacion
    {
        $ruta = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($ruta, $contenido);

        try {
            return app(ImportarEstudiantes::class)->handle($ruta, $pnf, $seccion);
        } finally {
            unlink($ruta);
        }
    }
}
