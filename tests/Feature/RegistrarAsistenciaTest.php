<?php

namespace Tests\Feature;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoSesion;
use App\Enums\MetodoAsistencia;
use App\Filament\App\Resources\Cursos\Pages\ListCursos;
use App\Filament\App\Resources\Cursos\Pages\RegistrarAsistencia;
use App\Filament\App\Resources\Cursos\Pages\ViewCurso;
use App\Filament\App\Resources\Cursos\RelationManagers\SesionesRelationManager;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Periodo;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrarAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-20 10:00:00');
    }

    public function test_el_docente_registra_la_asistencia_de_todos_los_inscritos(): void
    {
        [$pnf, $curso, $docente] = $this->cursoConDocente();
        [$ana, $luis, $marta] = $this->inscribir($curso, 3);
        $sesion = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-19']);

        $this->entrarAlPanel($docente, $pnf);

        Livewire::test(RegistrarAsistencia::class, ['record' => $curso->id, 'sesion' => $sesion->id])
            ->assertSchemaStateSet(['estados' => [$ana->id => 'presente', $luis->id => 'presente', $marta->id => 'presente']])
            ->fillForm(['estados' => [$ana->id => 'presente', $luis->id => 'ausente', $marta->id => 'tardanza']])
            ->call('guardar')
            ->assertHasNoFormErrors()
            ->assertRedirect(ViewCurso::getUrl(['record' => $curso]));

        $this->assertSame(EstadoSesion::Realizada, $sesion->fresh()->estado);
        $this->assertSame(
            [$ana->id => EstadoAsistencia::Presente, $luis->id => EstadoAsistencia::Ausente, $marta->id => EstadoAsistencia::Tardanza],
            $sesion->asistencias()->orderBy('estudiante_id')->pluck('estado', 'estudiante_id')->all(),
        );
        $asistencia = $sesion->asistencias()->first();
        $this->assertSame(MetodoAsistencia::Manual, $asistencia->metodo);
        $this->assertSame($docente->id, $asistencia->registrado_por);
        $this->assertSame($pnf->id, $asistencia->pnf_id);
    }

    public function test_marca_a_todos_ausentes_con_la_accion_masiva(): void
    {
        [$pnf, $curso, $docente] = $this->cursoConDocente();
        [$ana, $luis] = $this->inscribir($curso, 2);
        $sesion = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-19']);

        $this->entrarAlPanel($docente, $pnf);

        Livewire::test(RegistrarAsistencia::class, ['record' => $curso->id, 'sesion' => $sesion->id])
            ->callAction('todosAusentes')
            ->call('guardar');

        $this->assertSame(2, $sesion->asistencias()->where('estado', EstadoAsistencia::Ausente)->count());
    }

    public function test_al_corregir_solo_actualiza_los_estados_que_cambian(): void
    {
        [$pnf, $curso, $docente] = $this->cursoConDocente();
        [$ana, $luis] = $this->inscribir($curso, 2);
        $sesion = SesionClase::factory()->for($curso)->realizada()->create(['fecha' => '2026-10-19']);
        $otroDocente = User::factory()->docenteDe($pnf)->create();
        $deAna = Asistencia::factory()->for($sesion, 'sesionable')->for($ana)->create(['registrado_por' => $otroDocente->id]);
        Asistencia::factory()->for($sesion, 'sesionable')->for($luis)->create(['registrado_por' => $otroDocente->id]);

        $this->entrarAlPanel($docente, $pnf);

        Livewire::test(RegistrarAsistencia::class, ['record' => $curso->id, 'sesion' => $sesion->id])
            ->fillForm(['estados' => [$ana->id => 'presente', $luis->id => 'ausente']])
            ->call('guardar');

        $this->assertSame($otroDocente->id, $deAna->fresh()->registrado_por);
        $this->assertDatabaseHas('asistencias', ['estudiante_id' => $luis->id, 'estado' => 'ausente', 'registrado_por' => $docente->id]);
        $this->assertSame(2, Asistencia::count());
    }

    public function test_el_coordinador_puede_registrar_en_cualquier_curso_del_pnf(): void
    {
        [$pnf, $curso] = $this->cursoConDocente();
        $this->inscribir($curso, 1);
        $sesion = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-19']);

        $this->actingAs(User::factory()->coordinadorDe($pnf)->create())
            ->get(RegistrarAsistencia::getUrl(['record' => $curso, 'sesion' => $sesion], tenant: $pnf))
            ->assertOk()
            ->assertSee('Guardar asistencia');
    }

    public function test_el_docente_ajeno_al_curso_recibe_404(): void
    {
        [$pnf, $curso] = $this->cursoConDocente();
        $sesion = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-19']);

        $this->actingAs(User::factory()->docenteDe($pnf)->create())
            ->get(RegistrarAsistencia::getUrl(['record' => $curso, 'sesion' => $sesion], tenant: $pnf))
            ->assertNotFound();

        $this->assertSame(0, Asistencia::count());
    }

    public function test_un_curso_de_otro_pnf_recibe_404(): void
    {
        [$pnf] = $this->cursoConDocente();
        [, $cursoAjeno] = $this->cursoConDocente();
        $sesion = SesionClase::factory()->for($cursoAjeno)->create(['fecha' => '2026-10-19']);

        $this->actingAs(User::factory()->coordinadorDe($pnf)->create())
            ->get(RegistrarAsistencia::getUrl(['record' => $cursoAjeno, 'sesion' => $sesion], tenant: $pnf))
            ->assertNotFound();
    }

    public function test_una_sesion_de_otro_curso_recibe_404(): void
    {
        [$pnf, $curso, $docente] = $this->cursoConDocente();
        $sesionAjena = SesionClase::factory()->for(Curso::factory()->for($curso->seccion))->create(['fecha' => '2026-10-19']);

        $this->actingAs($docente)
            ->get(RegistrarAsistencia::getUrl(['record' => $curso, 'sesion' => $sesionAjena], tenant: $pnf))
            ->assertNotFound();
    }

    public function test_no_se_registra_una_sesion_futura_ni_suspendida(): void
    {
        [$pnf, $curso, $docente] = $this->cursoConDocente();
        $futura = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-21']);
        $suspendida = SesionClase::factory()->for($curso)->suspendida()->create(['fecha' => '2026-10-19']);

        $this->actingAs($docente);

        $this->get(RegistrarAsistencia::getUrl(['record' => $curso, 'sesion' => $futura], tenant: $pnf))->assertForbidden();
        $this->get(RegistrarAsistencia::getUrl(['record' => $curso, 'sesion' => $suspendida], tenant: $pnf))->assertForbidden();
    }

    public function test_el_coordinador_genera_las_sesiones_de_los_cursos_del_periodo_activo(): void
    {
        $pnf = Pnf::factory()->create();
        $activo = Periodo::factory()->activo()->create(['fecha_inicio' => '2026-10-05', 'fecha_fin' => '2026-10-11']);
        $anterior = Periodo::factory()->create(['fecha_inicio' => '2026-03-02', 'fecha_fin' => '2026-03-08']);
        $trayecto = Trayecto::factory()->for($pnf)->create();
        $delPeriodo = Curso::factory()->for(Seccion::factory()->for($trayecto)->for($activo))->create();
        $viejo = Curso::factory()->for(Seccion::factory()->for($trayecto)->for($anterior))->create();
        Horario::factory()->for($delPeriodo)->create(['dia_semana' => 1]);
        Horario::factory()->for($viejo)->create(['dia_semana' => 1]);

        $this->entrarAlPanel(User::factory()->coordinadorDe($pnf)->create(), $pnf);

        Livewire::test(ListCursos::class)
            ->callAction('generarSesiones')
            ->assertNotified('Se crearon 1 sesiones');

        $this->assertSame(1, $delPeriodo->sesiones()->count());
        $this->assertSame(0, $viejo->sesiones()->count());
    }

    public function test_el_docente_no_ve_la_accion_de_generar_sesiones(): void
    {
        [$pnf, $curso, $docente] = $this->cursoConDocente();

        $this->entrarAlPanel($docente, $pnf);

        Livewire::test(SesionesRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->assertActionHidden(TestAction::make('generarSesiones')->table())
            ->assertActionVisible(TestAction::make('create')->table());
    }

    public function test_el_docente_crea_una_sesion_extra(): void
    {
        [$pnf, $curso, $docente] = $this->cursoConDocente();

        $this->entrarAlPanel($docente, $pnf);

        Livewire::test(SesionesRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'fecha' => '2026-10-24',
                'hora_inicio' => '08:00',
                'hora_fin' => '10:00',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sesiones_clase', ['curso_id' => $curso->id, 'tipo' => 'extra', 'pnf_id' => $pnf->id]);
    }

    /**
     * @return array{0: Pnf, 1: Curso, 2: User}
     */
    private function cursoConDocente(): array
    {
        $pnf = Pnf::factory()->create();
        $docente = User::factory()->docenteDe($pnf)->create();
        $curso = Curso::factory()
            ->for(Seccion::factory()->for(Trayecto::factory()->for($pnf)))
            ->hasAttached($docente, relationship: 'docentes')
            ->create();

        return [$pnf, $curso, $docente];
    }

    /**
     * @return array<int, Estudiante>
     */
    private function inscribir(Curso $curso, int $cantidad): array
    {
        $estudiantes = Estudiante::factory()->count($cantidad)->for($curso->pnf)->sequence(
            ...array_map(fn (int $i): array => ['apellidos' => "Apellido {$i}"], range(1, $cantidad)),
        )->create();
        $curso->seccion->estudiantes()->attach($estudiantes);

        return $estudiantes->all();
    }

    private function entrarAlPanel(User $user, Pnf $pnf): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);
    }
}
