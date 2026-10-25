<?php

namespace Database\Seeders;

use App\Actions\AgregarIntegrantes;
use App\Actions\GenerarSesionesClase;
use App\Actions\RegistrarAsistencias;
use App\Enums\DiaSemana;
use App\Enums\EstadoAsistencia;
use App\Enums\ModalidadProyecto;
use App\Enums\RolPnf;
use App\Enums\Turno;
use App\Enums\VeredictoProyecto;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Feriado;
use App\Models\Justificacion;
use App\Models\Periodo;
use App\Models\Pnf;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Sede;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\UnidadCurricular;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de prueba para desarrollo local.
 *
 * Todas las cuentas usan la contraseña "password" (ver las factories).
 *  - Superadmin:   admin@gestionpnf.test        → /admin y /app
 *  - Coordinador:  coordinador@gestionpnf.test  → /app (PNF Informática)
 *  - Docente:      docente@gestionpnf.test      → /app (Informática y Administración)
 *  - Tutor técnico: tecnico@gestionpnf.test     → /app (Informática, tutor técnico de sus proyectos)
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

        $tecnico = User::factory()->create([
            'name' => 'Docente Técnico',
            'email' => 'tecnico@gestionpnf.test',
        ]);
        $tecnico->pnfs()->attach($informatica, ['rol' => RolPnf::Docente]);

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

        $this->registrarAsistencias($docente, $maria);
        $this->crearProyectos($docente, $tecnico, $maria);
    }

    /**
     * Arma grupos de proyecto en los cursos de la UC Proyecto con sus
     * tutorías semanales (hasta ayer) y la asistencia de los integrantes.
     * En Informática el tutor técnico es "Docente Técnico". El primer grupo
     * (el de María) ya tiene un veredicto.
     */
    private function crearProyectos(User $docente, User $tecnico, Estudiante $maria): void
    {
        $registrar = app(RegistrarAsistencias::class);
        $titulos = ['Sistema de control de inventario para el consejo comunal', 'Plataforma de citas para el ambulatorio', 'Registro digital de la biblioteca escolar', 'Gestión de la cooperativa de transporte'];

        foreach (Curso::query()->deProyecto()->with(['estudiantes', 'seccion.trayecto', 'seccion.periodo'])->get() as $curso) {
            $trayecto = $curso->seccion->trayecto;
            $tutorTecnico = $tecnico->pnfs->contains('id', $curso->pnf_id) ? $tecnico : null;
            $estudiantes = $curso->estudiantes->sortByDesc(fn (Estudiante $estudiante): bool => $estudiante->is($maria))->values();

            foreach ($estudiantes->chunk($trayecto->max_integrantes)->take(3) as $numero => $grupo) {
                $proyecto = Proyecto::create([
                    'pnf_id' => $curso->pnf_id,
                    'curso_id' => $curso->id,
                    'titulo' => $titulos[$numero % count($titulos)],
                    'comunidad' => 'Consejo comunal '.fake()->lastName(),
                    'tutor_tecnico_id' => $tutorTecnico?->id,
                    'tutor_institucional' => fake()->name(),
                    'tutor_institucional_contacto' => '0414-'.fake()->numerify('#######'),
                ]);
                app(AgregarIntegrantes::class)->handle($proyecto, $grupo->modelKeys());

                for ($fecha = $curso->seccion->periodo->fecha_inicio->copy()->next('Thursday'); $fecha->lt(today()); $fecha->addWeek()) {
                    $tutoria = $proyecto->tutorias()->create([
                        'pnf_id' => $curso->pnf_id,
                        'fecha' => $fecha->toDateString(),
                        'hora_inicio' => '14:00',
                        'hora_fin' => '15:30',
                        'tema' => fake()->randomElement(['Diagnóstico comunitario', 'Plan de acción', 'Revisión del prototipo', 'Avance del informe']),
                    ]);

                    $registrar->handle($tutoria, $grupo->mapWithKeys(fn (Estudiante $estudiante): array => [
                        $estudiante->id => fake()->boolean(12) ? EstadoAsistencia::Ausente : EstadoAsistencia::Presente,
                    ])->all(), $docente);
                }
            }
        }

        Proyecto::query()->conIntegrante($maria)->first()?->integrantes()->whereBelongsTo($maria)->first()
            ?->registrarVeredicto(VeredictoProyecto::Aprobado, $docente, 'Cumplió con todas las entregas.');
    }

    /**
     * Genera las sesiones de todos los cursos y registra la asistencia de las
     * clases ya ocurridas (hasta ayer). María falta a una de cada tres clases
     * de Matemática, así que supera el umbral, y tiene una justificación pendiente.
     */
    private function registrarAsistencias(User $docente, Estudiante $maria): void
    {
        $registrar = app(RegistrarAsistencias::class);

        foreach (Curso::with(['unidadCurricular', 'estudiantes'])->get() as $curso) {
            app(GenerarSesionesClase::class)->handle($curso);

            $curso->sesiones()
                ->whereDate('fecha', '<', today())
                ->orderBy('fecha')
                ->get()
                ->each(function (SesionClase $sesion, int $numero) use ($curso, $docente, $maria, $registrar): void {
                    $estados = $curso->estudiantes->mapWithKeys(fn (Estudiante $estudiante): array => [
                        $estudiante->id => match (true) {
                            $estudiante->is($maria) => $curso->unidadCurricular->codigo === 'PNFI-MAT' && $numero % 3 === 0
                                ? EstadoAsistencia::Ausente
                                : EstadoAsistencia::Presente,
                            fake()->boolean(8) => EstadoAsistencia::Ausente,
                            fake()->boolean(6) => EstadoAsistencia::Tardanza,
                            default => EstadoAsistencia::Presente,
                        },
                    ]);

                    $registrar->handle($sesion, $estados->all(), $docente);
                });
        }

        $ausencia = $maria->asistencias()->where('estado', EstadoAsistencia::Ausente)->latest('id')->first();

        Storage::disk(Justificacion::DISCO)->put(
            Justificacion::DIRECTORIO.'/ejemplo-constancia.pdf',
            Pdf::loadHTML('<p>Constancia médica de ejemplo.</p>')->output(),
        );

        Justificacion::create([
            'pnf_id' => $maria->pnf_id,
            'asistencia_id' => $ausencia->id,
            'estudiante_id' => $maria->id,
            'motivo' => 'Cita médica en el ambulatorio.',
            'soporte_path' => Justificacion::DIRECTORIO.'/ejemplo-constancia.pdf',
            'soporte_nombre' => 'constancia.pdf',
        ]);
    }

    /**
     * Crea los trayectos con sus unidades curriculares, una sección del
     * Trayecto I con los estudiantes inscritos y sus cursos con horario.
     * Casi todos cursan todas las unidades de la sección; los tres primeros
     * (sin contar a María) solo cursan la primera, como quien va atrasado.
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
            $parciales = $estudiantes->reject(fn (Estudiante $estudiante): bool => $estudiante->cedula === '30111222')->take(3);

            foreach ($trayecto->unidadesCurriculares()->withoutGlobalScopes()->get() as $indice => $unidadCurricular) {
                $curso = Curso::create([
                    'pnf_id' => $pnf->id,
                    'seccion_id' => $seccion->id,
                    'unidad_curricular_id' => $unidadCurricular->id,
                ]);
                $curso->docentes()->attach($docente);
                $curso->estudiantes()->attach(($indice === 0 ? $estudiantes : $estudiantes->diff($parciales))->modelKeys());
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
