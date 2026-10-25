<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Cursos\Pages\QrDinamicoSesion;
use App\Filament\App\Resources\Proyectos\Pages\QrDinamicoTutoria;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
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

class QrDinamicoWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_docente_proyecta_el_qr_durante_la_clase(): void
    {
        $this->travelTo('2026-10-20 08:05:00');
        [$pnf, $sesion, $docente] = $this->escenario();

        $this->actingAs($docente)
            ->get(QrDinamicoSesion::getUrl(['record' => $sesion->curso, 'sesion' => $sesion], tenant: $pnf))
            ->assertOk()
            ->assertSee('alt="Código QR de asistencia"', escape: false)
            ->assertSee('El código cambia cada 30 segundos');
    }

    public function test_fuera_del_horario_avisa_que_no_esta_abierta(): void
    {
        $this->travelTo('2026-10-20 07:00:00');
        [$pnf, $sesion, $docente] = $this->escenario();

        $this->actingAs($docente)
            ->get(QrDinamicoSesion::getUrl(['record' => $sesion->curso, 'sesion' => $sesion], tenant: $pnf))
            ->assertOk()
            ->assertSee('La toma por QR no está abierta')
            ->assertDontSee('alt="Código QR de asistencia"', escape: false);
    }

    public function test_un_docente_ajeno_recibe_404(): void
    {
        $this->travelTo('2026-10-20 08:05:00');
        [$pnf, $sesion] = $this->escenario();

        $this->actingAs(User::factory()->docenteDe($pnf)->create())
            ->get(QrDinamicoSesion::getUrl(['record' => $sesion->curso, 'sesion' => $sesion], tenant: $pnf))
            ->assertNotFound();
    }

    public function test_cerrar_marca_ausentes_a_los_que_no_escanearon(): void
    {
        $this->travelTo('2026-10-20 08:05:00');
        [$pnf, $sesion, $docente, $estudiante] = $this->escenario();
        $faltante = Estudiante::factory()->for($pnf)->create();
        $sesion->curso->estudiantes()->attach($faltante);
        Asistencia::factory()->for($sesion, 'sesionable')->for($estudiante)->create();

        $this->actingAs($docente);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);

        Livewire::test(QrDinamicoSesion::class, ['record' => $sesion->curso_id, 'sesion' => $sesion->id])
            ->assertSeeText('1 / 2')
            ->callAction('cerrar')
            ->assertNotified('Asistencia cerrada');

        $this->assertDatabaseHas('asistencias', ['estudiante_id' => $faltante->id, 'estado' => 'ausente']);
    }

    public function test_el_tutor_proyecta_el_qr_de_la_tutoria(): void
    {
        $this->travelTo('2026-10-20 14:05:00');
        [$pnf, $sesion] = $this->escenario();
        $tecnico = User::factory()->docenteDe($pnf)->create();
        $proyecto = Proyecto::factory()->for(Curso::factory()->for($sesion->curso->seccion))->create(['tutor_tecnico_id' => $tecnico->id]);
        $tutoria = Tutoria::factory()->for($proyecto)->create(['fecha' => '2026-10-20', 'hora_inicio' => '14:00:00', 'hora_fin' => '15:30:00']);

        $this->actingAs($tecnico)
            ->get(QrDinamicoTutoria::getUrl(['record' => $proyecto, 'tutoria' => $tutoria], tenant: $pnf))
            ->assertOk()
            ->assertSee('alt="Código QR de asistencia"', escape: false);
    }

    /**
     * @return array{0: Pnf, 1: SesionClase, 2: User, 3: Estudiante}
     */
    private function escenario(): array
    {
        $pnf = Pnf::factory()->create();
        $docente = User::factory()->docenteDe($pnf)->create();
        $curso = Curso::factory()->for(Seccion::factory()->for(Trayecto::factory()->for($pnf)))->hasAttached($docente, relationship: 'docentes')->create();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $curso->estudiantes()->attach($estudiante);
        $sesion = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-20', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:15:00']);

        return [$pnf, $sesion, $docente, $estudiante];
    }
}
