<?php

namespace Tests\Feature\Api;

use App\Actions\AgregarIntegrantes;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Justificacion;
use App\Models\Pnf;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Sede;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\Tutoria;
use App\Models\UnidadCurricular;
use App\Models\User;
use App\Support\CodigoQrDinamico;
use App\Support\PnfActual;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EstudianteTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_sus_cursos_con_el_porcentaje_de_inasistencia(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        Curso::factory()->for($curso->seccion)->create();
        foreach (['ausente', 'presente', 'presente', 'presente'] as $estado) {
            Asistencia::factory()->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')->for($estudiante)->create(['estado' => $estado]);
        }
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->getJson('/api/v1/estudiante/cursos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $curso->id)
            ->assertJsonPath('data.0.resumen.porcentaje', 25)
            ->assertJsonPath('data.0.resumen.nivel.valor', 'en_riesgo');
    }

    public function test_ve_solo_sus_asistencias_y_no_las_de_cursos_ajenos(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $companero = Estudiante::factory()->for($curso->pnf)->create();
        $sesion = SesionClase::factory()->for($curso)->realizada()->create();
        $propia = Asistencia::factory()->for($sesion, 'sesionable')->for($estudiante)->ausente()->create();
        Asistencia::factory()->for($sesion, 'sesionable')->for($companero)->create();
        $ajeno = Curso::factory()->for($curso->seccion)->create();
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->getJson("/api/v1/estudiante/cursos/{$curso->id}/asistencias")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $propia->id)
            ->assertJsonPath('data.0.puede_justificarse', true);

        $this->getJson("/api/v1/estudiante/cursos/{$ajeno->id}/asistencias")->assertNotFound();
    }

    public function test_registra_su_asistencia_con_el_qr_dinamico(): void
    {
        $this->travelTo('2026-10-20 08:20:00');
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $sesion = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-20', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:15:00']);
        $codigo = $this->codigoDe($sesion);
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->postJson('/api/v1/estudiante/asistencias/qr', ['codigo' => $codigo])
            ->assertCreated()
            ->assertJsonPath('estado.valor', 'tardanza');

        $this->postJson('/api/v1/estudiante/asistencias/qr', ['codigo' => $codigo])
            ->assertOk()
            ->assertJsonPath('message', 'Ya tenías tu asistencia registrada en esta sesión.');

        $this->assertDatabaseHas('asistencias', ['estudiante_id' => $estudiante->id, 'metodo' => 'qr_dinamico', 'registrado_por' => null]);
    }

    public function test_el_qr_dinamico_exige_la_ubicacion_de_la_sede(): void
    {
        $this->travelTo('2026-10-20 08:00:00');
        $sede = Sede::create(['nombre' => 'Sede Principal', 'latitud' => 8.1222, 'longitud' => -63.5497, 'radio_metros' => 150]);
        [$curso, $estudiante] = $this->cursoConEstudiante($sede);
        $sesion = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-20', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:15:00']);
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->postJson('/api/v1/estudiante/asistencias/qr', ['codigo' => $this->codigoDe($sesion), 'latitud' => 8.2, 'longitud' => -63.5497])
            ->assertJsonValidationErrors(['latitud' => 'Debes estar en la sede Sede Principal para registrar tu asistencia.']);

        $this->postJson('/api/v1/estudiante/asistencias/qr', ['codigo' => $this->codigoDe($sesion), 'latitud' => 8.1223, 'longitud' => -63.5498])
            ->assertCreated();
    }

    public function test_no_registra_con_un_codigo_de_otro_pnf(): void
    {
        $this->travelTo('2026-10-20 08:00:00');
        [, $estudiante] = $this->cursoConEstudiante();
        [$otroCurso] = $this->cursoConEstudiante();
        $ajena = SesionClase::factory()->for($otroCurso)->create(['fecha' => '2026-10-20', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:15:00']);
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->postJson('/api/v1/estudiante/asistencias/qr', ['codigo' => $this->codigoDe($ajena)])
            ->assertJsonValidationErrors(['codigo' => 'La sesión del código no existe.']);

        $this->assertSame(0, Asistencia::count());
    }

    public function test_solicita_una_justificacion_con_su_soporte(): void
    {
        Storage::fake('local');
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $ausencia = Asistencia::factory()->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')->for($estudiante)->ausente()->create();
        $ajena = Asistencia::factory()->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')->ausente()->create();
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->postJson("/api/v1/estudiante/asistencias/{$ausencia->id}/justificaciones", ['motivo' => 'Reposo', 'soporte' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])
            ->assertJsonValidationErrors(['soporte' => 'El soporte debe ser un PDF o una imagen JPG o PNG.']);
        $this->postJson("/api/v1/estudiante/asistencias/{$ajena->id}/justificaciones", ['motivo' => 'Reposo', 'soporte' => UploadedFile::fake()->create('reposo.pdf', 10, 'application/pdf')])
            ->assertNotFound();

        $this->postJson("/api/v1/estudiante/asistencias/{$ausencia->id}/justificaciones", ['motivo' => 'Reposo médico', 'soporte' => UploadedFile::fake()->create('reposo.pdf', 100, 'application/pdf')])
            ->assertCreated()
            ->assertJsonPath('data.estado.valor', 'pendiente');

        $justificacion = Justificacion::sole();
        Storage::disk('local')->assertExists($justificacion->soporte_path);
        $this->getJson('/api/v1/estudiante/justificaciones')->assertJsonCount(1, 'data')->assertJsonPath('data.0.soporte', 'reposo.pdf');
    }

    public function test_ve_su_proyecto_y_sus_tutorias(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $cursoProyecto = Curso::factory()->for($curso->seccion)->for(UnidadCurricular::factory()->for($curso->seccion->trayecto)->state(['es_proyecto' => true]))->create();
        $cursoProyecto->estudiantes()->attach($estudiante);
        $tecnico = User::factory()->docenteDe($curso->pnf)->create(['name' => 'Ing. Pedro Lara']);
        $proyecto = Proyecto::factory()->for($cursoProyecto)->create(['tutor_tecnico_id' => $tecnico->id, 'tutor_institucional' => 'Rosa Marcano']);
        app(AgregarIntegrantes::class)->handle($proyecto, [$estudiante->id]);
        Asistencia::factory()->for(Tutoria::factory()->for($proyecto)->realizada(), 'sesionable')->for($estudiante)->create();
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->getJson('/api/v1/estudiante/proyectos')
            ->assertOk()
            ->assertJsonPath('data.0.tutores.tecnico', 'Ing. Pedro Lara')
            ->assertJsonPath('data.0.tutores.institucional.nombre', 'Rosa Marcano')
            ->assertJsonPath('data.0.resumen.sesiones', 1)
            ->assertJsonPath('data.0.veredicto', null);

        $this->getJson("/api/v1/estudiante/proyectos/{$proyecto->id}/tutorias")
            ->assertOk()
            ->assertJsonPath('data.0.sesion.tipo', 'tutoria');
        $this->getJson('/api/v1/estudiante/proyectos/'.Proyecto::factory()->for($cursoProyecto)->create()->id.'/tutorias')->assertNotFound();
    }

    public function test_entrega_el_carnet_digital(): void
    {
        [, $estudiante] = $this->cursoConEstudiante();
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->getJson('/api/v1/estudiante/carnet')
            ->assertOk()
            ->assertJsonPath('qr', $estudiante->contenidoQr())
            ->assertJsonPath('cedula', $estudiante->cedula);
    }

    public function test_lista_y_marca_como_leidas_sus_notificaciones(): void
    {
        [, $estudiante] = $this->cursoConEstudiante();
        FilamentNotification::make()->title('Tu justificación fue aprobada')->body('Matemática')->sendToDatabase($estudiante);
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonPath('sin_leer', 1)
            ->assertJsonPath('data.0.titulo', 'Tu justificación fue aprobada')
            ->assertJsonPath('data.0.leida', false);

        $this->postJson('/api/v1/notificaciones/leer')->assertOk();

        $this->assertSame(0, $estudiante->unreadNotifications()->count());
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

    private function codigoDe(SesionClase $sesion): string
    {
        $codigo = app(CodigoQrDinamico::class)->generar($sesion)['codigo'];
        app(PnfActual::class)->limpiar();

        return $codigo;
    }
}
