<?php

namespace Tests\Feature;

use App\Actions\AgregarIntegrantes;
use App\Actions\CalcularInasistencias;
use App\Enums\EstadoSesion;
use App\Enums\NivelInasistencia;
use App\Enums\VeredictoProyecto;
use App\Filament\App\Resources\Proyectos\Pages\CreateProyecto;
use App\Filament\App\Resources\Proyectos\Pages\RegistrarAsistenciaTutoria;
use App\Filament\App\Resources\Proyectos\Pages\ViewProyecto;
use App\Filament\App\Resources\Proyectos\RelationManagers\IntegrantesRelationManager;
use App\Filament\App\Resources\Proyectos\RelationManagers\TutoriasRelationManager;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\IntegranteProyecto;
use App\Models\Pnf;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\Tutoria;
use App\Models\UnidadCurricular;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProyectosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-20 10:00:00');
    }

    public function test_el_docente_crea_un_proyecto_en_su_curso_de_proyecto(): void
    {
        [$pnf, $curso, $tutor] = $this->cursoDeProyecto();

        $this->entrar($tutor, $pnf);

        Livewire::test(CreateProyecto::class)
            ->fillForm(['curso_id' => $curso->id, 'titulo' => 'Sistema de inventario comunal', 'comunidad' => 'Consejo comunal La Sabanita'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('proyectos', ['curso_id' => $curso->id, 'pnf_id' => $pnf->id, 'titulo' => 'Sistema de inventario comunal']);
    }

    public function test_el_docente_no_crea_proyectos_en_cursos_ajenos_ni_en_ucs_que_no_son_de_proyecto(): void
    {
        [$pnf, $curso] = $this->cursoDeProyecto();
        $docente = User::factory()->docenteDe($pnf)->create();
        $deClases = Curso::factory()->for($curso->seccion)->hasAttached($docente, relationship: 'docentes')->create();

        $this->entrar($docente, $pnf);

        foreach ([$curso, $deClases] as $cursoInvalido) {
            Livewire::test(CreateProyecto::class)
                ->fillForm(['curso_id' => $cursoInvalido->id, 'titulo' => 'Proyecto'])
                ->call('create')
                ->assertHasFormErrors(['curso_id']);
        }

        $this->assertSame(0, Proyecto::count());
    }

    public function test_el_tutor_ve_su_proyecto_pero_no_los_ajenos_ni_de_otro_pnf(): void
    {
        [$pnf, $curso, $tutor] = $this->cursoDeProyecto();
        $suyo = Proyecto::factory()->for($curso)->create();
        $ajeno = Proyecto::factory()->for(Curso::factory()->for($curso->seccion)->for(UnidadCurricular::factory()->for($curso->seccion->trayecto)->state(['es_proyecto' => true])))->create();
        $deOtroPnf = Proyecto::factory()->create();

        $this->actingAs($tutor);

        $this->get(ViewProyecto::getUrl(['record' => $suyo], tenant: $pnf))->assertOk();
        $this->get(ViewProyecto::getUrl(['record' => $ajeno], tenant: $pnf))->assertNotFound();
        $this->get(ViewProyecto::getUrl(['record' => $deOtroPnf], tenant: $pnf))->assertNotFound();
    }

    public function test_agrega_integrantes_inscritos_en_el_curso(): void
    {
        [$pnf, $curso] = $this->cursoDeProyecto(min: 2, max: 3);
        $proyecto = Proyecto::factory()->for($curso)->create();
        $inscritos = $this->inscribir($curso, 2);

        app(AgregarIntegrantes::class)->handle($proyecto, $inscritos->modelKeys());

        $this->assertEqualsCanonicalizing($inscritos->modelKeys(), $proyecto->integrantes()->pluck('estudiante_id')->all());
        $this->assertSame($curso->id, IntegranteProyecto::first()->curso_id);
        $this->assertFalse($proyecto->estaIncompleto());
    }

    public static function integrantesInvalidos(): array
    {
        return [
            'no inscrito en el curso' => ['no_inscrito', 'Solo pueden ser integrantes los estudiantes inscritos en el curso.'],
            'en otro proyecto del curso' => ['en_otro_proyecto', 'Algún estudiante ya pertenece a otro proyecto de este curso.'],
            'supera el máximo' => ['excede_maximo', 'El proyecto admite como máximo 1 integrante.'],
        ];
    }

    #[DataProvider('integrantesInvalidos')]
    public function test_rechaza_integrantes_invalidos(string $caso, string $mensaje): void
    {
        [$pnf, $curso] = $this->cursoDeProyecto(min: 1, max: 1);
        $proyecto = Proyecto::factory()->for($curso)->create();
        $estudiante = match ($caso) {
            'no_inscrito' => Estudiante::factory()->for($pnf)->create(),
            default => $this->inscribir($curso, 1)->first(),
        };

        if ($caso === 'en_otro_proyecto') {
            IntegranteProyecto::factory()->for(Proyecto::factory()->for($curso))->for($estudiante)->create();
        }

        if ($caso === 'excede_maximo') {
            app(AgregarIntegrantes::class)->handle($proyecto, [$this->inscribir($curso, 1)->first()->id]);
        }

        try {
            app(AgregarIntegrantes::class)->handle($proyecto, [$estudiante->id]);
            $this->fail('Se esperaba un error de validación.');
        } catch (ValidationException $error) {
            $this->assertSame($mensaje, $error->errors()['estudiantes'][0]);
        }

        $this->assertFalse($proyecto->integrantes()->whereBelongsTo($estudiante)->exists());
    }

    public function test_el_tutor_agrega_integrantes_desde_el_proyecto(): void
    {
        [$pnf, $curso, $tutor] = $this->cursoDeProyecto();
        $proyecto = Proyecto::factory()->for($curso)->create();
        $estudiante = $this->inscribir($curso, 1)->first();

        $this->entrar($tutor, $pnf);

        Livewire::test(IntegrantesRelationManager::class, ['ownerRecord' => $proyecto, 'pageClass' => ViewProyecto::class])
            ->callAction(TestAction::make('agregar')->table(), data: ['estudiantes' => [$estudiante->id]])
            ->assertHasNoFormErrors()
            ->assertNotified('Integrantes agregados');

        $this->assertTrue($proyecto->estudiantes->contains($estudiante));
    }

    public function test_avisa_en_el_formulario_cuando_se_supera_el_maximo(): void
    {
        [$pnf, $curso, $tutor] = $this->cursoDeProyecto(min: 1, max: 1);
        $proyecto = Proyecto::factory()->for($curso)->create();
        $estudiantes = $this->inscribir($curso, 2);

        $this->entrar($tutor, $pnf);

        Livewire::test(IntegrantesRelationManager::class, ['ownerRecord' => $proyecto, 'pageClass' => ViewProyecto::class])
            ->callAction(TestAction::make('agregar')->table(), data: ['estudiantes' => $estudiantes->modelKeys()])
            ->assertHasFormErrors(['estudiantes' => 'El proyecto admite como máximo 1 integrante.']);

        $this->assertSame(0, $proyecto->integrantes()->count());
    }

    public function test_el_tutor_crea_una_tutoria_y_toma_asistencia_solo_a_los_integrantes(): void
    {
        [$pnf, $curso, $tutor] = $this->cursoDeProyecto();
        $proyecto = Proyecto::factory()->for($curso)->create();
        [$ana, $luis] = $this->integrantes($proyecto, 2);
        $this->inscribir($curso, 1);

        $this->entrar($tutor, $pnf);

        Livewire::test(TutoriasRelationManager::class, ['ownerRecord' => $proyecto, 'pageClass' => ViewProyecto::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'tema' => 'Revisión del diagnóstico',
                'fecha' => '2026-10-20',
                'hora_inicio' => '14:00',
                'hora_fin' => '15:30',
            ])
            ->assertHasNoFormErrors();

        $tutoria = Tutoria::sole();

        Livewire::test(RegistrarAsistenciaTutoria::class, ['record' => $proyecto->id, 'tutoria' => $tutoria->id])
            ->assertSchemaStateSet(['estados' => [$ana->id => 'presente', $luis->id => 'presente']])
            ->fillForm(['estados' => [$ana->id => 'presente', $luis->id => 'ausente']])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertSame(EstadoSesion::Realizada, $tutoria->fresh()->estado);
        $this->assertSame(2, $tutoria->asistencias()->count());
        $this->assertDatabaseHas('asistencias', ['sesionable_type' => 'tutoria', 'sesionable_id' => $tutoria->id, 'estudiante_id' => $luis->id, 'estado' => 'ausente']);
    }

    public function test_un_docente_ajeno_no_toma_asistencia_de_la_tutoria(): void
    {
        [$pnf, $curso] = $this->cursoDeProyecto();
        $tutoria = Tutoria::factory()->for(Proyecto::factory()->for($curso))->create(['fecha' => '2026-10-19']);

        $this->actingAs(User::factory()->docenteDe($pnf)->create())
            ->get(RegistrarAsistenciaTutoria::getUrl(['record' => $tutoria->proyecto_id, 'tutoria' => $tutoria], tenant: $pnf))
            ->assertNotFound();
    }

    public function test_la_inasistencia_a_tutorias_usa_el_umbral_del_trayecto_y_no_mezcla_clases(): void
    {
        [$pnf, $curso] = $this->cursoDeProyecto(umbralTutorias: 30);
        $proyecto = Proyecto::factory()->for($curso)->create();
        [$ana] = $this->integrantes($proyecto, 1);
        foreach (['ausente', 'presente', 'presente', 'presente'] as $estado) {
            Asistencia::factory()->for(Tutoria::factory()->for($proyecto)->realizada(), 'sesionable')->for($ana)->create(['estado' => $estado]);
        }
        Asistencia::factory()->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')->for($ana)->ausente()->create();

        $tutorias = app(CalcularInasistencias::class)->deProyecto($proyecto)->get($ana->id);
        $clases = app(CalcularInasistencias::class)->handle($curso)->get($ana->id);

        $this->assertSame(4, $tutorias->sesiones);
        $this->assertSame(25.0, $tutorias->porcentaje);
        $this->assertSame(NivelInasistencia::EnRiesgo, $tutorias->nivel);
        $this->assertSame(100.0, $clases->porcentaje);
    }

    public function test_sugiere_reprobado_y_el_tutor_registra_el_veredicto(): void
    {
        [$pnf, $curso, $tutor] = $this->cursoDeProyecto(umbralTutorias: 25);
        $proyecto = Proyecto::factory()->for($curso)->create();
        [$ana] = $this->integrantes($proyecto, 1);
        foreach (['ausente', 'ausente', 'presente'] as $estado) {
            Asistencia::factory()->for(Tutoria::factory()->for($proyecto)->realizada(), 'sesionable')->for($ana)->create(['estado' => $estado]);
        }
        $integrante = $proyecto->integrantes()->sole();

        $this->entrar($tutor, $pnf);

        Livewire::test(IntegrantesRelationManager::class, ['ownerRecord' => $proyecto, 'pageClass' => ViewProyecto::class])
            ->mountAction(TestAction::make('veredicto')->table($integrante))
            ->assertActionDataSet(['veredicto' => VeredictoProyecto::Reprobado])
            ->setActionData(['veredicto' => 'aprobado', 'observacion' => 'Recuperó con trabajo comunitario'])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $integrante->refresh();
        $this->assertSame(VeredictoProyecto::Aprobado, $integrante->veredicto);
        $this->assertSame($tutor->id, $integrante->veredicto_por);
    }

    public function test_descarga_el_acta_del_proyecto(): void
    {
        [$pnf, $curso, $tutor] = $this->cursoDeProyecto();
        $proyecto = Proyecto::factory()->for($curso)->create();
        $this->integrantes($proyecto, 2);

        $this->entrar($tutor, $pnf);

        Livewire::test(ViewProyecto::class, ['record' => $proyecto->id])
            ->callAction('acta')
            ->assertFileDownloaded(contentType: 'application/pdf');
    }

    /**
     * @return array{0: Pnf, 1: Curso, 2: User}
     */
    private function cursoDeProyecto(int $min = 1, int $max = 5, float $umbralTutorias = 25): array
    {
        $pnf = Pnf::factory()->create();
        $tutor = User::factory()->docenteDe($pnf)->create();
        $trayecto = Trayecto::factory()->for($pnf)->create(['min_integrantes' => $min, 'max_integrantes' => $max, 'umbral_inasistencia_tutorias' => $umbralTutorias]);
        $curso = Curso::factory()
            ->for(Seccion::factory()->for($trayecto))
            ->for(UnidadCurricular::factory()->for($trayecto)->state(['es_proyecto' => true]))
            ->hasAttached($tutor, relationship: 'docentes')
            ->create();

        return [$pnf, $curso, $tutor];
    }

    private function inscribir(Curso $curso, int $cantidad): Collection
    {
        $estudiantes = Estudiante::factory()->count($cantidad)->for($curso->pnf)->create();
        $curso->estudiantes()->attach($estudiantes);

        return $estudiantes;
    }

    /**
     * @return array<int, Estudiante>
     */
    private function integrantes(Proyecto $proyecto, int $cantidad): array
    {
        $estudiantes = $this->inscribir($proyecto->curso, $cantidad);
        app(AgregarIntegrantes::class)->handle($proyecto, $estudiantes->modelKeys());

        return $estudiantes->sortBy('id')->values()->all();
    }

    private function entrar(User $user, Pnf $pnf): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);
    }
}
