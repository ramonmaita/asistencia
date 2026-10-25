<?php

namespace Tests\Feature\Api;

use App\Actions\AgregarIntegrantes;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\Tutoria;
use App\Models\UnidadCurricular;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocenteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-20 08:05:00');
    }

    public function test_lista_solo_los_pnf_del_docente(): void
    {
        [$pnf, , $docente] = $this->escenario();
        Pnf::factory()->create();
        Sanctum::actingAs($docente, ['docente']);

        $this->getJson('/api/v1/docente/pnfs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $pnf->slug)
            ->assertJsonPath('data.0.rol.valor', 'docente');
    }

    public function test_un_pnf_ajeno_da_404(): void
    {
        [, , $docente] = $this->escenario();
        Sanctum::actingAs($docente, ['docente']);

        $this->getJson('/api/v1/docente/pnfs/'.Pnf::factory()->create()->slug.'/sesiones/hoy')->assertNotFound();
    }

    public function test_lista_las_clases_y_tutorias_de_hoy_del_docente(): void
    {
        [$pnf, $sesion, $docente] = $this->escenario();
        SesionClase::factory()->for($sesion->curso)->create(['fecha' => '2026-10-19']);
        SesionClase::factory()->for(Curso::factory()->for($sesion->curso->seccion))->create(['fecha' => '2026-10-20']);
        $tutoria = Tutoria::factory()->for($this->proyectoConTecnico($sesion->curso->seccion, $docente))->create(['fecha' => '2026-10-20', 'hora_inicio' => '14:00:00']);
        Sanctum::actingAs($docente, ['docente']);

        $this->getJson("/api/v1/docente/pnfs/{$pnf->slug}/sesiones/hoy")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.tipo', 'clase')
            ->assertJsonPath('data.0.id', $sesion->id)
            ->assertJsonPath('data.0.asistencia_abierta', true)
            ->assertJsonPath('data.0.convocados', 1)
            ->assertJsonPath('data.1.tipo', 'tutoria')
            ->assertJsonPath('data.1.id', $tutoria->id);
    }

    public function test_muestra_la_sesion_con_sus_convocados(): void
    {
        [$pnf, $sesion, $docente, $estudiante] = $this->escenario();
        Asistencia::factory()->for($sesion, 'sesionable')->for($estudiante)->tardanza()->create();
        Sanctum::actingAs($docente, ['docente']);

        $this->getJson("/api/v1/docente/pnfs/{$pnf->slug}/sesiones/clase/{$sesion->id}")
            ->assertOk()
            ->assertJsonPath('data.participantes.0.id', $estudiante->id)
            ->assertJsonPath('data.participantes.0.estado.valor', 'tardanza');
    }

    public function test_una_sesion_de_un_curso_ajeno_da_404(): void
    {
        [$pnf, $sesion] = $this->escenario();
        Sanctum::actingAs(User::factory()->docenteDe($pnf)->create(), ['docente']);

        $this->getJson("/api/v1/docente/pnfs/{$pnf->slug}/sesiones/clase/{$sesion->id}")->assertNotFound();
        $this->postJson("/api/v1/docente/pnfs/{$pnf->slug}/sesiones/clase/{$sesion->id}/escanear", ['qr' => 'GPNF:x'])->assertNotFound();
    }

    public function test_escanea_el_carnet_y_registra_la_asistencia(): void
    {
        [$pnf, $sesion, $docente, $estudiante] = $this->escenario();
        Sanctum::actingAs($docente, ['docente']);
        $url = "/api/v1/docente/pnfs/{$pnf->slug}/sesiones/clase/{$sesion->id}/escanear";

        $this->postJson($url, ['qr' => $estudiante->contenidoQr()])
            ->assertCreated()
            ->assertJsonPath('estudiante.id', $estudiante->id)
            ->assertJsonPath('estado.valor', 'presente');

        $this->postJson($url, ['qr' => $estudiante->contenidoQr()])
            ->assertOk()
            ->assertJsonPath('message', 'El estudiante ya tenía su asistencia registrada.');

        $this->assertDatabaseHas('asistencias', ['estudiante_id' => $estudiante->id, 'metodo' => 'qr_docente', 'registrado_por' => $docente->id]);
    }

    public function test_rechaza_el_carnet_de_otro_pnf_y_fuera_de_la_clase(): void
    {
        [$pnf, $sesion, $docente, $estudiante] = $this->escenario();
        Sanctum::actingAs($docente, ['docente']);
        $url = "/api/v1/docente/pnfs/{$pnf->slug}/sesiones/clase/{$sesion->id}/escanear";

        $this->postJson($url, ['qr' => Estudiante::factory()->create()->contenidoQr()])
            ->assertJsonValidationErrors(['qr' => 'El carnet no es válido o no pertenece a este PNF.']);

        $this->travelTo('2026-10-20 11:00:00');

        $this->postJson($url, ['qr' => $estudiante->contenidoQr()])
            ->assertJsonValidationErrors(['qr' => 'La sesión ya terminó; pide a tu docente que registre la asistencia manualmente.']);

        $this->assertSame(0, Asistencia::count());
    }

    public function test_entrega_el_codigo_del_qr_dinamico_solo_durante_la_clase(): void
    {
        [$pnf, $sesion, $docente] = $this->escenario();
        Sanctum::actingAs($docente, ['docente']);
        $url = "/api/v1/docente/pnfs/{$pnf->slug}/sesiones/clase/{$sesion->id}/qr-dinamico";

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('segundos', 30)
            ->assertJsonPath('expira_at', '2026-10-20T08:05:30-04:00');
        $this->assertStringStartsWith("GPNFD:sesion_clase:{$sesion->id}:", $this->getJson($url)->json('codigo'));

        $this->travelTo('2026-10-20 07:00:00');

        $this->getJson($url)->assertUnprocessable()->assertJsonValidationErrors(['sesion']);
    }

    public function test_registra_manualmente_y_cierra_con_ausentes(): void
    {
        [$pnf, $sesion, $docente, $estudiante] = $this->escenario();
        $faltante = Estudiante::factory()->for($pnf)->create();
        $sesion->curso->estudiantes()->attach($faltante);
        Sanctum::actingAs($docente, ['docente']);
        $base = "/api/v1/docente/pnfs/{$pnf->slug}/sesiones/clase/{$sesion->id}";

        $this->postJson("{$base}/asistencias", ['estados' => [$estudiante->id => 'feliz']])->assertJsonValidationErrors(['estados.'.$estudiante->id]);
        $this->postJson("{$base}/asistencias", ['estados' => [Estudiante::factory()->for($pnf)->create()->id => 'presente']])->assertJsonValidationErrors(['estados']);
        $this->postJson("{$base}/asistencias", ['estados' => [$estudiante->id => 'tardanza']])->assertOk();

        $this->postJson("{$base}/cerrar")->assertOk()->assertJsonPath('ausentes', 1);

        $this->assertDatabaseHas('asistencias', ['estudiante_id' => $estudiante->id, 'estado' => 'tardanza', 'metodo' => 'manual']);
        $this->assertDatabaseHas('asistencias', ['estudiante_id' => $faltante->id, 'estado' => 'ausente']);
    }

    public function test_no_registra_una_sesion_futura(): void
    {
        [$pnf, $sesion, $docente, $estudiante] = $this->escenario();
        $futura = SesionClase::factory()->for($sesion->curso)->create(['fecha' => '2026-10-27']);
        Sanctum::actingAs($docente, ['docente']);

        $this->postJson("/api/v1/docente/pnfs/{$pnf->slug}/sesiones/clase/{$futura->id}/asistencias", ['estados' => [$estudiante->id => 'presente']])
            ->assertForbidden();
    }

    public function test_el_tutor_tecnico_toma_asistencia_de_la_tutoria(): void
    {
        [$pnf, $sesion, , $estudiante] = $this->escenario();
        $tecnico = User::factory()->docenteDe($pnf)->create();
        $proyecto = $this->proyectoConTecnico($sesion->curso->seccion, $tecnico, $estudiante);
        $tutoria = Tutoria::factory()->for($proyecto)->create(['fecha' => '2026-10-20', 'hora_inicio' => '08:00:00', 'hora_fin' => '09:00:00']);
        Sanctum::actingAs($tecnico, ['docente']);

        $this->postJson("/api/v1/docente/pnfs/{$pnf->slug}/sesiones/tutoria/{$tutoria->id}/escanear", ['qr' => $estudiante->contenidoQr()])
            ->assertCreated();

        $this->assertDatabaseHas('asistencias', ['sesionable_type' => 'tutoria', 'sesionable_id' => $tutoria->id, 'estudiante_id' => $estudiante->id]);
    }

    /**
     * Clase de 08:00 a 10:15 del 20/10/2026 con su docente y un estudiante inscrito.
     *
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

    private function proyectoConTecnico(Seccion $seccion, User $tecnico, ?Estudiante $integrante = null): Proyecto
    {
        $curso = Curso::factory()->for($seccion)->for(UnidadCurricular::factory()->for($seccion->trayecto)->state(['es_proyecto' => true]))->create();
        $proyecto = Proyecto::factory()->for($curso)->create(['tutor_tecnico_id' => $tecnico->id]);

        if ($integrante) {
            $curso->estudiantes()->attach($integrante);
            app(AgregarIntegrantes::class)->handle($proyecto, [$integrante->id]);
        }

        return $proyecto;
    }
}
