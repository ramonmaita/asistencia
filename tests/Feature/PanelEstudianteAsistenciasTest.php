<?php

namespace Tests\Feature;

use App\Filament\Estudiante\Resources\Cursos\Pages\ListCursos;
use App\Filament\Estudiante\Resources\Justificaciones\Pages\ListJustificaciones;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Justificacion;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Support\PnfActual;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelEstudianteAsistenciasTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_solo_los_cursos_de_sus_secciones(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $deOtraSeccion = Curso::factory()->for(Seccion::factory()->for($curso->seccion->trayecto))->create();

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(ListCursos::class)
            ->assertCanSeeTableRecords([$curso])
            ->assertCanNotSeeTableRecords([$deOtraSeccion]);
    }

    public function test_muestra_el_porcentaje_y_el_estado_del_curso(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        foreach (['ausente', 'presente', 'presente', 'presente'] as $estado) {
            Asistencia::factory()->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')->for($estudiante)->create(['estado' => $estado]);
        }

        $this->actingAs($estudiante, 'estudiante')
            ->get("/estudiante/cursos/{$curso->id}")
            ->assertOk()
            ->assertSee('25,00 % · En riesgo');
    }

    public function test_un_curso_ajeno_da_404(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        [$cursoDeOtroPnf] = $this->cursoConEstudiante();
        $mismoPnfOtraSeccion = Curso::factory()->for(Seccion::factory()->for($curso->seccion->trayecto))->create();

        $this->actingAs($estudiante, 'estudiante');

        $this->get("/estudiante/cursos/{$cursoDeOtroPnf->id}")->assertNotFound();
        $this->get("/estudiante/cursos/{$mismoPnfOtraSeccion->id}")->assertNotFound();
    }

    public function test_lista_solo_sus_justificaciones(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $companero = Estudiante::factory()->for($curso->pnf)->create();
        $propia = Justificacion::factory()->for(Asistencia::factory()->ausente()->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')->for($estudiante))->create();
        $ajena = Justificacion::factory()->for(Asistencia::factory()->ausente()->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')->for($companero))->create();

        $this->entrarComoEstudiante($estudiante);

        Livewire::test(ListJustificaciones::class)
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$ajena]);
    }

    public function test_las_pantallas_del_estudiante_cargan(): void
    {
        [, $estudiante] = $this->cursoConEstudiante();

        $this->actingAs($estudiante, 'estudiante');

        $this->get('/estudiante/cursos')->assertOk()->assertSee('Mis asistencias');
        $this->get('/estudiante/justificaciones')->assertOk()->assertSee('Mis justificaciones');
    }

    /**
     * @return array{0: Curso, 1: Estudiante}
     */
    private function cursoConEstudiante(): array
    {
        $pnf = Pnf::factory()->create();
        $curso = Curso::factory()->for(Seccion::factory()->for(Trayecto::factory()->for($pnf)))->create();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $curso->seccion->estudiantes()->attach($estudiante);

        return [$curso, $estudiante];
    }

    private function entrarComoEstudiante(Estudiante $estudiante): void
    {
        $this->actingAs($estudiante, 'estudiante');
        Filament::setCurrentPanel('estudiante');
        app(PnfActual::class)->establecer($estudiante->pnf_id);
    }
}
