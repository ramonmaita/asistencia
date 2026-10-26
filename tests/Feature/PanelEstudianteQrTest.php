<?php

namespace Tests\Feature;

use App\Filament\Estudiante\Pages\MiCarnet;
use App\Filament\Estudiante\Pages\RegistrarAsistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\Sede;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Support\CodigoQrDinamico;
use App\Support\PnfActual;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelEstudianteQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_carnet_virtual(): void
    {
        [, $estudiante] = $this->cursoConEstudiante();

        $this->actingAs($estudiante, 'estudiante')
            ->get(MiCarnet::getUrl(panel: 'estudiante'))
            ->assertOk()
            ->assertSee('alt="QR de mi carnet"', escape: false)
            ->assertSee($estudiante->apellidos);
    }

    public function test_registra_su_asistencia_leyendo_el_qr_dinamico_desde_la_web(): void
    {
        $this->travelTo('2026-10-20 08:05:00');
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $sesion = $this->sesionDe($curso);
        $codigo = $this->codigoDe($sesion);
        $this->entrarComoEstudiante($estudiante);

        Livewire::test(RegistrarAsistencia::class)
            ->call('registrar', $codigo)
            ->assertReturned(fn (array $resultado): bool => $resultado['exito'] && str_contains($resultado['mensaje'], 'Presente'));

        $this->assertDatabaseHas('asistencias', ['estudiante_id' => $estudiante->id, 'estado' => 'presente', 'metodo' => 'qr_dinamico']);
    }

    public function test_rechaza_la_lectura_fuera_de_la_sede(): void
    {
        $this->travelTo('2026-10-20 08:05:00');
        $sede = Sede::create(['nombre' => 'Sede Principal', 'latitud' => 8.1222, 'longitud' => -63.5497, 'radio_metros' => 150]);
        [$curso, $estudiante] = $this->cursoConEstudiante($sede);
        $codigo = $this->codigoDe($this->sesionDe($curso));
        $this->entrarComoEstudiante($estudiante);

        Livewire::test(RegistrarAsistencia::class)
            ->call('registrar', $codigo, 8.2, -63.5497)
            ->assertReturned(['exito' => false, 'mensaje' => 'Debes estar en la sede Sede Principal para registrar tu asistencia.']);

        $this->assertDatabaseCount('asistencias', 0);
    }

    /**
     * @return array{0: Curso, 1: Estudiante}
     */
    private function cursoConEstudiante(?Sede $sede = null): array
    {
        $pnf = Pnf::factory()->create();
        $curso = Curso::factory()->for(Seccion::factory()->for(Trayecto::factory()->for($pnf))->state(['sede_id' => $sede?->id]))->create();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $curso->estudiantes()->attach($estudiante);

        return [$curso, $estudiante];
    }

    private function sesionDe(Curso $curso): SesionClase
    {
        return SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-20', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:15:00']);
    }

    private function codigoDe(SesionClase $sesion): string
    {
        return app(CodigoQrDinamico::class)->generar($sesion)['codigo'];
    }

    private function entrarComoEstudiante(Estudiante $estudiante): void
    {
        $this->actingAs($estudiante, 'estudiante');
        Filament::setCurrentPanel('estudiante');
        app(PnfActual::class)->establecer($estudiante->pnf_id);
    }
}
