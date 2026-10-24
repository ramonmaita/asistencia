<?php

namespace Tests\Feature;

use App\Actions\CalcularInasistencias;
use App\Enums\NivelInasistencia;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CalcularInasistenciasTest extends TestCase
{
    use RefreshDatabase;

    public function test_cuenta_solo_las_ausencias_de_sesiones_realizadas(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $this->registrar($curso, $estudiante, ['presente', 'tardanza', 'ausente', 'justificado']);
        $programada = SesionClase::factory()->for($curso)->create();
        Asistencia::factory()->for($programada, 'sesionable')->for($estudiante)->ausente()->create();

        $resumen = app(CalcularInasistencias::class)->handle($curso)->get($estudiante->id);

        $this->assertSame(4, $resumen->sesiones);
        $this->assertSame(2, $resumen->asistencias());
        $this->assertSame(1, $resumen->ausentes);
        $this->assertSame(1, $resumen->justificadas);
        $this->assertSame(25.0, $resumen->porcentaje);
    }

    public function test_no_mezcla_asistencias_de_otro_curso(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();
        $this->registrar($curso, $estudiante, ['presente']);
        $otroCurso = Curso::factory()->for($curso->seccion)->create();
        $this->registrar($otroCurso, $estudiante, ['ausente', 'ausente']);

        $resumen = app(CalcularInasistencias::class)->handle($curso)->get($estudiante->id);

        $this->assertSame(0.0, $resumen->porcentaje);
        $this->assertSame(NivelInasistencia::Normal, $resumen->nivel);
    }

    public function test_incluye_a_los_inscritos_sin_registros(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante();

        $resumen = app(CalcularInasistencias::class)->handle($curso)->get($estudiante->id);

        $this->assertSame(0, $resumen->sesiones);
        $this->assertSame(NivelInasistencia::Normal, $resumen->nivel);
    }

    /**
     * Con umbral 25 %: riesgo desde 20 % (80 % del umbral) y superado por encima de 25 %.
     */
    public static function nivelesSegunAusencias(): array
    {
        return [
            '1 de 10 (10 %)' => [1, NivelInasistencia::Normal],
            '2 de 10 (20 %)' => [2, NivelInasistencia::EnRiesgo],
            '1 de 4 (25 %, justo el umbral)' => [1, NivelInasistencia::EnRiesgo, 4],
            '3 de 10 (30 %)' => [3, NivelInasistencia::Superado],
        ];
    }

    #[DataProvider('nivelesSegunAusencias')]
    public function test_compara_el_porcentaje_con_el_umbral_del_pnf(int $ausencias, NivelInasistencia $esperado, int $sesiones = 10): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante(umbral: 25);
        $this->registrar($curso, $estudiante, [
            ...array_fill(0, $ausencias, 'ausente'),
            ...array_fill(0, $sesiones - $ausencias, 'presente'),
        ]);

        $resumen = app(CalcularInasistencias::class)->handle($curso)->get($estudiante->id);

        $this->assertSame($esperado, $resumen->nivel);
    }

    public function test_usa_el_umbral_propio_del_pnf(): void
    {
        [$curso, $estudiante] = $this->cursoConEstudiante(umbral: 15);
        $this->registrar($curso, $estudiante, ['ausente', ...array_fill(0, 4, 'presente')]);

        $resumen = app(CalcularInasistencias::class)->handle($curso)->get($estudiante->id);

        $this->assertSame(20.0, $resumen->porcentaje);
        $this->assertSame(NivelInasistencia::Superado, $resumen->nivel);
    }

    /**
     * @return array{0: Curso, 1: Estudiante}
     */
    private function cursoConEstudiante(float $umbral = 25): array
    {
        $pnf = Pnf::factory()->create(['umbral_inasistencia_clases' => $umbral]);
        $curso = Curso::factory()->for(Seccion::factory()->for(Trayecto::factory()->for($pnf)))->create();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $curso->seccion->estudiantes()->attach($estudiante);

        return [$curso, $estudiante];
    }

    /**
     * Crea una sesión realizada por cada estado, con la asistencia del estudiante.
     *
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
