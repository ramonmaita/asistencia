<?php

namespace Tests\Feature;

use App\Actions\AgregarIntegrantes;
use App\Enums\VeredictoProyecto;
use App\Filament\App\Resources\Justificaciones\Pages\ListJustificaciones;
use App\Filament\Estudiante\Resources\Proyectos\Pages\ListProyectos;
use App\Filament\Estudiante\Resources\Proyectos\Pages\ViewProyecto;
use App\Filament\Estudiante\Resources\Proyectos\RelationManagers\TutoriasRelationManager;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Justificacion;
use App\Models\Pnf;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Trayecto;
use App\Models\Tutoria;
use App\Models\UnidadCurricular;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PanelEstudianteProyectoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_estudiante_ve_solo_su_proyecto_con_su_veredicto(): void
    {
        [$proyecto, $estudiante] = $this->proyectoConIntegrante();
        $ajeno = Proyecto::factory()->for($proyecto->curso)->create();
        $proyecto->integrantes()->sole()->registrarVeredicto(VeredictoProyecto::Aprobado, $proyecto->curso->docentes->first());

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(ListProyectos::class)
            ->assertCanSeeTableRecords([$proyecto])
            ->assertCanNotSeeTableRecords([$ajeno]);

        $this->get(ViewProyecto::getUrl(['record' => $proyecto], panel: 'estudiante'))->assertOk()->assertSee('Aprobado');
        $this->get(ViewProyecto::getUrl(['record' => $ajeno], panel: 'estudiante'))->assertNotFound();
    }

    public function test_justifica_una_inasistencia_a_tutoria_y_el_tutor_la_ve(): void
    {
        Storage::fake('local');
        [$proyecto, $estudiante, $tutor] = $this->proyectoConIntegrante();
        $ausencia = Asistencia::factory()->for(Tutoria::factory()->for($proyecto)->realizada(), 'sesionable')->for($estudiante)->ausente()->create();

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(TutoriasRelationManager::class, ['ownerRecord' => $proyecto, 'pageClass' => ViewProyecto::class])
            ->assertCanSeeTableRecords([$ausencia])
            ->callAction(TestAction::make('justificar')->table($ausencia), data: [
                'motivo' => 'Cita médica',
                'soporte' => UploadedFile::fake()->create('cita.pdf', 100, 'application/pdf'),
            ])
            ->assertHasNoFormErrors();

        $justificacion = Justificacion::sole();
        $this->assertSame(1, $tutor->notifications()->count());

        $this->actingAs($tutor, 'web');
        Filament::setCurrentPanel('app');
        Filament::setTenant($proyecto->pnf);
        app(PnfActual::class)->establecer($proyecto->pnf);

        Livewire::test(ListJustificaciones::class)->assertCanSeeTableRecords([$justificacion]);
    }

    /**
     * @return array{0: Proyecto, 1: Estudiante, 2: User}
     */
    private function proyectoConIntegrante(): array
    {
        $pnf = Pnf::factory()->create();
        $tutor = User::factory()->docenteDe($pnf)->create();
        $trayecto = Trayecto::factory()->for($pnf)->create();
        $curso = Curso::factory()
            ->for(Seccion::factory()->for($trayecto))
            ->for(UnidadCurricular::factory()->for($trayecto)->state(['es_proyecto' => true]))
            ->hasAttached($tutor, relationship: 'docentes')
            ->create();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $curso->estudiantes()->attach($estudiante);
        $proyecto = Proyecto::factory()->for($curso)->create();
        app(AgregarIntegrantes::class)->handle($proyecto, [$estudiante->id]);

        return [$proyecto, $estudiante, $tutor];
    }

    private function entrarComoEstudiante(Estudiante $estudiante): void
    {
        $this->actingAs($estudiante, 'estudiante');
        Filament::setCurrentPanel('estudiante');
        app(PnfActual::class)->establecer($estudiante->pnf_id);
    }
}
