<?php

namespace Tests\Feature;

use App\Filament\App\Pages\AuditoriaAsistencias;
use App\Filament\App\Resources\Cursos\Pages\ListCursos;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Justificacion;
use App\Models\Pnf;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\Tutoria;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolAdministrativoTest extends TestCase
{
    use RefreshDatabase;

    public function test_entra_a_estudiantes_personal_y_auditoria(): void
    {
        $pnf = Pnf::factory()->create();

        $this->actingAs(User::factory()->administrativoDe($pnf)->create());

        $this->get("/app/{$pnf->slug}/estudiantes")->assertOk();
        $this->get("/app/{$pnf->slug}/estudiantes/create")->assertOk();
        $this->get("/app/{$pnf->slug}/personal")->assertOk();
        $this->get(AuditoriaAsistencias::getUrl(tenant: $pnf))->assertOk();
    }

    public function test_ve_todos_los_cursos_e_inscribe_pero_no_los_modifica(): void
    {
        [$pnf, $curso] = $this->pnfConCurso();
        $administrativo = User::factory()->administrativoDe($pnf)->create();
        $this->entrarAlPanel($administrativo, $pnf);

        Livewire::test(ListCursos::class)->assertCanSeeTableRecords([$curso]);

        $this->assertTrue($administrativo->can('gestionarInscripcion', $curso));
        $this->assertFalse($administrativo->can('update', $curso));
        $this->assertFalse($administrativo->can('create', Curso::class));
    }

    public function test_no_registra_asistencia_ni_crea_sesiones(): void
    {
        [$pnf, $curso] = $this->pnfConCurso();
        $sesion = SesionClase::factory()->for($curso)->realizada()->create();
        $administrativo = User::factory()->administrativoDe($pnf)->create();
        $this->entrarAlPanel($administrativo, $pnf);

        $this->assertFalse($administrativo->can('registrarAsistencia', $sesion));
        $this->assertFalse($administrativo->can('create', SesionClase::class));
    }

    public function test_consulta_los_proyectos_y_justificaciones_sin_gestionarlos(): void
    {
        [$pnf, $curso] = $this->pnfConCurso();
        $proyecto = Proyecto::factory()->for($curso)->create();
        $justificacion = Justificacion::factory()->for(Asistencia::factory()->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')->ausente())->create();
        $administrativo = User::factory()->administrativoDe($pnf)->create();
        $this->entrarAlPanel($administrativo, $pnf);

        $this->assertTrue($administrativo->can('view', $proyecto));
        $this->assertFalse($administrativo->can('update', $proyecto));
        $this->assertFalse($administrativo->can('create', Tutoria::class));
        $this->assertTrue($administrativo->can('view', $justificacion));
        $this->assertFalse($administrativo->can('revisar', $justificacion));
    }

    /**
     * @return array{0: Pnf, 1: Curso}
     */
    private function pnfConCurso(): array
    {
        $pnf = Pnf::factory()->create();
        $curso = Curso::factory()
            ->for(Seccion::factory()->for(Trayecto::factory()->for($pnf)))
            ->hasAttached(User::factory()->docenteDe($pnf), relationship: 'docentes')
            ->create();

        return [$pnf, $curso];
    }

    private function entrarAlPanel(User $usuario, Pnf $pnf): void
    {
        $this->actingAs($usuario);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);
    }
}
