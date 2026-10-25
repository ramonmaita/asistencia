<?php

namespace Tests\Feature;

use App\Enums\NivelInasistencia;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Periodo;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\User;
use App\Notifications\NivelInasistenciaAlcanzado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificarInasistenciasTest extends TestCase
{
    use RefreshDatabase;

    public function test_avisa_al_estudiante_al_docente_y_al_coordinador_cuando_entra_en_riesgo(): void
    {
        Notification::fake();
        [$curso, $estudiante, $docente, $coordinador] = $this->escenario();
        $this->registrar($curso, $estudiante, ['ausente', 'presente', 'presente', 'presente', 'presente']);

        $this->artisan('asistencias:notificar-inasistencias')->assertSuccessful();

        foreach ([$estudiante, $docente, $coordinador] as $destinatario) {
            Notification::assertSentTo($destinatario, NivelInasistenciaAlcanzado::class, fn (NivelInasistenciaAlcanzado $aviso): bool => $aviso->nivel === NivelInasistencia::EnRiesgo && $aviso->porcentaje === 20.0);
        }
        Notification::assertCount(3);
        $this->assertDatabaseHas('avisos_inasistencia', ['curso_id' => $curso->id, 'estudiante_id' => $estudiante->id, 'nivel' => 'en_riesgo']);
    }

    public function test_no_repite_un_aviso_ya_enviado(): void
    {
        [$curso, $estudiante] = $this->escenario();
        $this->registrar($curso, $estudiante, ['ausente', 'presente', 'presente', 'presente', 'presente']);
        $this->artisan('asistencias:notificar-inasistencias');
        Notification::fake();

        $this->artisan('asistencias:notificar-inasistencias')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_avisa_otra_vez_al_superar_el_umbral(): void
    {
        [$curso, $estudiante] = $this->escenario();
        $this->registrar($curso, $estudiante, ['ausente', 'presente', 'presente', 'presente', 'presente']);
        $this->artisan('asistencias:notificar-inasistencias');
        $this->registrar($curso, $estudiante, ['ausente']);
        Notification::fake();

        $this->artisan('asistencias:notificar-inasistencias');

        Notification::assertSentTo($estudiante, NivelInasistenciaAlcanzado::class, fn (NivelInasistenciaAlcanzado $aviso): bool => $aviso->nivel === NivelInasistencia::Superado);
    }

    public function test_no_avisa_a_quien_esta_normal_ni_en_cursos_de_otro_periodo(): void
    {
        Notification::fake();
        [$curso, $estudiante] = $this->escenario();
        $this->registrar($curso, $estudiante, ['presente', 'presente']);
        $cursoViejo = Curso::factory()->for(Seccion::factory()->for($curso->seccion->trayecto)->for(Periodo::factory()))->create();
        $cursoViejo->estudiantes()->attach($estudiante);
        $this->registrar($cursoViejo, $estudiante, ['ausente']);

        $this->artisan('asistencias:notificar-inasistencias')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_los_avisos_llegan_a_la_bandeja_y_al_correo(): void
    {
        [$curso, $estudiante] = $this->escenario();
        $this->registrar($curso, $estudiante, ['ausente', 'ausente']);

        $this->artisan('asistencias:notificar-inasistencias');

        $this->assertSame('Superaste el límite de inasistencias', $estudiante->notifications()->sole()->data['title']);
        $correo = (new NivelInasistenciaAlcanzado($curso, $estudiante, NivelInasistencia::Superado, 100))->toMail($estudiante);
        $this->assertStringContainsString('Tienes 100,00 % de inasistencias', implode(' ', $correo->introLines));
    }

    /**
     * @return array{0: Curso, 1: Estudiante, 2: User, 3: User}
     */
    private function escenario(): array
    {
        $pnf = Pnf::factory()->create(['umbral_inasistencia_clases' => 25]);
        $docente = User::factory()->docenteDe($pnf)->create();
        $coordinador = User::factory()->coordinadorDe($pnf)->create();
        $curso = Curso::factory()
            ->for(Seccion::factory()->for(Trayecto::factory()->for($pnf))->for(Periodo::factory()->activo()))
            ->hasAttached($docente, relationship: 'docentes')
            ->create();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $curso->estudiantes()->attach($estudiante);

        return [$curso, $estudiante, $docente, $coordinador];
    }

    /**
     * @param  array<int, string>  $estados
     */
    private function registrar(Curso $curso, Estudiante $estudiante, array $estados): void
    {
        foreach ($estados as $estado) {
            Asistencia::factory()
                ->for(SesionClase::factory()->for($curso)->realizada(), 'sesionable')
                ->for($estudiante)
                ->create(['estado' => $estado]);
        }
    }
}
