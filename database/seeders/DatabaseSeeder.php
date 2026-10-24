<?php

namespace Database\Seeders;

use App\Enums\DiaSemana;
use App\Enums\ModalidadProyecto;
use App\Enums\RolPnf;
use App\Enums\Turno;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Feriado;
use App\Models\Periodo;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\Sede;
use App\Models\Trayecto;
use App\Models\UnidadCurricular;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Datos de prueba para desarrollo local.
 *
 * Todas las cuentas usan la contraseña "password" (ver las factories).
 *  - Superadmin:   admin@gestionpnf.test        → /admin y /app
 *  - Coordinador:  coordinador@gestionpnf.test  → /app (PNF Informática)
 *  - Docente:      docente@gestionpnf.test      → /app (Informática y Administración)
 *  - Estudiante:   cédula 30111222              → /estudiante (Informática)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Sede::create(['nombre' => 'Sede Principal']);

        $periodo = Periodo::create([
            'nombre' => '2026-II',
            'fecha_inicio' => '2026-09-14',
            'fecha_fin' => '2027-02-12',
            'activo' => true,
        ]);

        foreach ([
            ['2026-10-12', 'Día de la Resistencia Indígena'],
            ['2026-12-24', 'Nochebuena'],
            ['2026-12-25', 'Navidad'],
            ['2026-12-31', 'Fin de año'],
            ['2027-01-01', 'Año nuevo'],
        ] as [$fecha, $descripcion]) {
            Feriado::create(['fecha' => $fecha, 'descripcion' => $descripcion]);
        }

        $informatica = Pnf::create(['nombre' => 'PNF en Informática', 'codigo' => 'PNFI']);
        $administracion = Pnf::create(['nombre' => 'PNF en Administración', 'codigo' => 'PNFA']);

        User::factory()->superadmin()->create([
            'name' => 'Administrador',
            'email' => 'admin@gestionpnf.test',
        ]);

        $coordinador = User::factory()->create([
            'name' => 'Coordinador Informática',
            'email' => 'coordinador@gestionpnf.test',
        ]);
        $coordinador->pnfs()->attach($informatica, ['rol' => RolPnf::Coordinador]);

        $docente = User::factory()->create([
            'name' => 'Docente Compartido',
            'email' => 'docente@gestionpnf.test',
        ]);
        $docente->pnfs()->attach($informatica, ['rol' => RolPnf::Docente]);
        $docente->pnfs()->attach($administracion, ['rol' => RolPnf::Docente]);

        $maria = Estudiante::factory()->for($informatica)->create([
            'cedula' => '30111222',
            'nombres' => 'María',
            'apellidos' => 'Pérez',
        ]);
        $estudiantesInformatica = Estudiante::factory()->count(20)->for($informatica)->create()->push($maria);
        $estudiantesAdministracion = Estudiante::factory()->count(15)->for($administracion)->create();

        $this->crearPlanDeEstudios($informatica, $periodo, $docente, $estudiantesInformatica, [
            'Trayecto I' => [['PNFI-ALG', 'Algorítmica y Programación'], ['PNFI-MAT', 'Matemática'], ['PNFI-PRO1', 'Proyecto Socio Tecnológico I', true]],
            'Trayecto II' => [['PNFI-BD', 'Base de Datos'], ['PNFI-PRO2', 'Proyecto Socio Tecnológico II', true]],
        ]);

        $this->crearPlanDeEstudios($administracion, $periodo, $docente, $estudiantesAdministracion, [
            'Trayecto I' => [['PNFA-CON', 'Contabilidad'], ['PNFA-PRO1', 'Proyecto Socio Integrador I', true]],
        ]);
    }

    /**
     * Crea los trayectos con sus unidades curriculares, una sección del
     * Trayecto I con los estudiantes inscritos y sus cursos con horario.
     *
     * @param  Collection<int, Estudiante>  $estudiantes
     * @param  array<string, array<int, array{0: string, 1: string, 2?: bool}>>  $plan
     */
    private function crearPlanDeEstudios(Pnf $pnf, Periodo $periodo, User $docente, Collection $estudiantes, array $plan): void
    {
        $numero = 0;

        foreach ($plan as $nombreTrayecto => $unidades) {
            $numero++;

            $trayecto = Trayecto::create([
                'pnf_id' => $pnf->id,
                'numero' => $numero,
                'nombre' => $nombreTrayecto,
                'modalidad_proyecto' => $numero === 1 ? ModalidadProyecto::Grupal : ModalidadProyecto::Individual,
                'min_integrantes' => 3,
                'max_integrantes' => 5,
            ]);

            foreach ($unidades as $unidad) {
                UnidadCurricular::create([
                    'pnf_id' => $pnf->id,
                    'trayecto_id' => $trayecto->id,
                    'codigo' => $unidad[0],
                    'nombre' => $unidad[1],
                    'horas_semanales' => 4,
                    'es_proyecto' => $unidad[2] ?? false,
                ]);
            }

            if ($numero > 1) {
                continue;
            }

            $seccion = Seccion::create([
                'pnf_id' => $pnf->id,
                'trayecto_id' => $trayecto->id,
                'periodo_id' => $periodo->id,
                'codigo' => 'Sección 01',
                'turno' => Turno::Manana,
            ]);
            $seccion->estudiantes()->attach($estudiantes->modelKeys());

            foreach ($trayecto->unidadesCurriculares()->withoutGlobalScopes()->get() as $indice => $unidadCurricular) {
                $curso = Curso::create([
                    'pnf_id' => $pnf->id,
                    'seccion_id' => $seccion->id,
                    'unidad_curricular_id' => $unidadCurricular->id,
                ]);
                $curso->docentes()->attach($docente);
                $curso->horarios()->create([
                    'dia_semana' => DiaSemana::from($indice + 1),
                    'hora_inicio' => '08:00',
                    'hora_fin' => '10:15',
                    'aula' => 'Aula '.($indice + 1),
                ]);
            }
        }
    }
}
