<?php

namespace Tests\Feature;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoJustificacion;
use App\Filament\App\Resources\Justificaciones\Pages\ListJustificaciones;
use App\Filament\Estudiante\Resources\Cursos\Pages\ViewCurso;
use App\Filament\Estudiante\Resources\Cursos\RelationManagers\AsistenciasRelationManager;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Justificacion;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class JustificacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_estudiante_solicita_la_justificacion_de_su_inasistencia(): void
    {
        Storage::fake('local');
        [$curso, $estudiante, $docente] = $this->cursoConEstudiante();
        $ausencia = $this->asistencia($curso, $estudiante, 'ausente');

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(AsistenciasRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->callAction(TestAction::make('justificar')->table($ausencia), data: [
                'motivo' => 'Reposo médico por gripe',
                'soporte' => UploadedFile::fake()->create('reposo.pdf', 300, 'application/pdf'),
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Solicitud enviada');

        $justificacion = Justificacion::sole();
        $this->assertSame($estudiante->id, $justificacion->estudiante_id);
        $this->assertSame(EstadoJustificacion::Pendiente, $justificacion->estado);
        $this->assertSame('reposo.pdf', $justificacion->soporte_nombre);
        Storage::disk('local')->assertExists($justificacion->soporte_path);
        $this->assertStringStartsWith('justificaciones/', $justificacion->soporte_path);
        $this->assertSame(1, $docente->notifications()->count());
    }

    public function test_rechaza_un_soporte_de_tipo_no_permitido(): void
    {
        Storage::fake('local');
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $ausencia = $this->asistencia($curso, $estudiante, 'ausente');

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(AsistenciasRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->callAction(TestAction::make('justificar')->table($ausencia), data: [
                'motivo' => 'Reposo',
                'soporte' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
            ])
            ->assertHasFormErrors(['soporte']);

        $this->assertSame(0, Justificacion::count());
    }

    public function test_rechaza_un_soporte_mayor_a_5_mb(): void
    {
        Storage::fake('local');
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $ausencia = $this->asistencia($curso, $estudiante, 'ausente');

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(AsistenciasRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->callAction(TestAction::make('justificar')->table($ausencia), data: [
                'motivo' => 'Reposo',
                'soporte' => UploadedFile::fake()->create('reposo.pdf', 6000, 'application/pdf'),
            ])
            ->assertHasFormErrors(['soporte']);

        $this->assertSame(0, Justificacion::count());
    }

    public function test_solo_se_justifica_una_ausencia_sin_solicitud_vigente(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $presente = $this->asistencia($curso, $estudiante, 'presente');
        $conSolicitud = $this->asistencia($curso, $estudiante, 'ausente');
        Justificacion::factory()->for($conSolicitud)->create();
        $rechazada = $this->asistencia($curso, $estudiante, 'ausente');
        Justificacion::factory()->for($rechazada)->create(['estado' => EstadoJustificacion::Rechazada]);

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(AsistenciasRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->assertActionHidden(TestAction::make('justificar')->table($presente))
            ->assertActionHidden(TestAction::make('justificar')->table($conSolicitud))
            ->assertActionVisible(TestAction::make('justificar')->table($rechazada));
    }

    public function test_el_estudiante_no_ve_las_asistencias_de_otro_estudiante(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $companero = Estudiante::factory()->for($curso->pnf)->create();
        $curso->estudiantes()->attach($companero);
        $propia = $this->asistencia($curso, $estudiante, 'ausente');
        $ajena = $this->asistencia($curso, $companero, 'ausente');

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(AsistenciasRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$ajena]);
    }

    public function test_el_docente_aprueba_y_la_asistencia_queda_justificada(): void
    {
        [$curso, $estudiante, $docente] = $this->cursoConEstudiante();
        $justificacion = Justificacion::factory()->for($this->asistencia($curso, $estudiante, 'ausente'))->create();

        $this->entrarAlPanel($docente, $curso->pnf);

        Livewire::test(ListJustificaciones::class)
            ->assertCanSeeTableRecords([$justificacion])
            ->callAction(TestAction::make('aprobar')->table($justificacion), data: ['observacion' => 'Reposo válido'])
            ->assertNotified('Justificación aprobada');

        $justificacion->refresh();
        $this->assertSame(EstadoJustificacion::Aprobada, $justificacion->estado);
        $this->assertSame($docente->id, $justificacion->revisada_por);
        $this->assertSame('Reposo válido', $justificacion->observacion_revision);
        $this->assertSame(EstadoAsistencia::Justificado, $justificacion->asistencia->estado);
        $this->assertSame(1, $estudiante->notifications()->count());
    }

    public function test_el_rechazo_exige_observacion_y_mantiene_la_ausencia(): void
    {
        [$curso, $estudiante, $docente] = $this->cursoConEstudiante();
        $justificacion = Justificacion::factory()->for($this->asistencia($curso, $estudiante, 'ausente'))->create();

        $this->entrarAlPanel($docente, $curso->pnf);

        Livewire::test(ListJustificaciones::class)
            ->callAction(TestAction::make('rechazar')->table($justificacion), data: ['observacion' => ''])
            ->assertHasFormErrors(['observacion' => 'required']);
        $this->assertTrue($justificacion->fresh()->estaPendiente());

        Livewire::test(ListJustificaciones::class)
            ->callAction(TestAction::make('rechazar')->table($justificacion), data: ['observacion' => 'El soporte no es legible'])
            ->assertHasNoFormErrors();

        $justificacion->refresh();
        $this->assertSame(EstadoJustificacion::Rechazada, $justificacion->estado);
        $this->assertSame(EstadoAsistencia::Ausente, $justificacion->asistencia->estado);
    }

    public function test_el_docente_no_ve_justificaciones_de_cursos_ajenos(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $justificacion = Justificacion::factory()->for($this->asistencia($curso, $estudiante, 'ausente'))->create();
        $docenteAjeno = User::factory()->docenteDe($curso->pnf)->create();

        $this->entrarAlPanel($docenteAjeno, $curso->pnf);

        Livewire::test(ListJustificaciones::class)
            ->assertCanNotSeeTableRecords([$justificacion]);
        $this->assertFalse($docenteAjeno->can('revisar', $justificacion));
    }

    public function test_el_coordinador_ve_las_del_pnf_pero_no_las_de_otro(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        [$otroCurso, $otroEstudiante] = $this->cursoConEstudiante();
        $propia = Justificacion::factory()->for($this->asistencia($curso, $estudiante, 'ausente'))->create();
        $deOtroPnf = Justificacion::factory()->for($this->asistencia($otroCurso, $otroEstudiante, 'ausente'))->create();

        $this->entrarAlPanel(User::factory()->coordinadorDe($curso->pnf)->create(), $curso->pnf);

        Livewire::test(ListJustificaciones::class)
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$deOtroPnf]);
    }

    /**
     * @return array{0: Curso, 1: Estudiante, 2: User}
     */
    private function cursoConEstudiante(): array
    {
        $pnf = Pnf::factory()->create();
        $docente = User::factory()->docenteDe($pnf)->create();
        $curso = Curso::factory()
            ->for(Seccion::factory()->for(Trayecto::factory()->for($pnf)))
            ->hasAttached($docente, relationship: 'docentes')
            ->create();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $curso->estudiantes()->attach($estudiante);

        return [$curso, $estudiante, $docente];
    }

    private function asistencia(Curso $curso, Estudiante $estudiante, string $estado): Asistencia
    {
        return Asistencia::factory()
            ->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')
            ->for($estudiante)
            ->create(['estado' => $estado]);
    }

    private function entrarComoEstudiante(Estudiante $estudiante): void
    {
        $this->actingAs($estudiante, 'estudiante');
        Filament::setCurrentPanel('estudiante');
        app(PnfActual::class)->establecer($estudiante->pnf_id);
    }

    private function entrarAlPanel(User $user, Pnf $pnf): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);
    }
}
