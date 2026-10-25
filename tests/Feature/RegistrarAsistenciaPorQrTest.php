<?php

namespace Tests\Feature;

use App\Actions\CerrarAsistencia;
use App\Actions\RegistrarAsistenciaPorQr;
use App\Actions\RegistrarAsistencias;
use App\Enums\EstadoAsistencia;
use App\Enums\EstadoSesion;
use App\Enums\MetodoAsistencia;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\Sede;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\User;
use App\Support\CodigoQrDinamico;
use App\Support\PnfActual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrarAsistenciaPorQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_codigo_dinamico_resuelve_la_sesion_mientras_no_caduca(): void
    {
        [$sesion] = $this->sesionDeHoy();
        $this->travelTo('2026-10-20 08:00:10');
        $codigo = app(CodigoQrDinamico::class)->generar($sesion)['codigo'];

        $this->travelTo('2026-10-20 08:00:40');

        $this->assertTrue(app(CodigoQrDinamico::class)->resolver($codigo)->is($sesion));
    }

    public static function codigosInvalidos(): array
    {
        return [
            'caducado' => ['caducado', 'El código QR caducó. Escanea el que se muestra ahora.'],
            'firma alterada' => ['alterado', 'El código no es un QR de asistencia válido.'],
            'carnet en lugar de QR dinámico' => ['carnet', 'El código no es un QR de asistencia válido.'],
        ];
    }

    #[DataProvider('codigosInvalidos')]
    public function test_rechaza_codigos_invalidos(string $caso, string $mensaje): void
    {
        [$sesion, $estudiante] = $this->sesionDeHoy();
        $this->travelTo('2026-10-20 08:00:10');
        $codigo = app(CodigoQrDinamico::class)->generar($sesion)['codigo'];

        $codigo = match ($caso) {
            'caducado' => tap($codigo, fn () => $this->travelTo('2026-10-20 08:01:05')),
            'alterado' => substr($codigo, 0, -1).(str_ends_with($codigo, 'a') ? 'b' : 'a'),
            'carnet' => $estudiante->contenidoQr(),
        };

        try {
            app(CodigoQrDinamico::class)->resolver($codigo);
            $this->fail('Se esperaba un error de validación.');
        } catch (ValidationException $error) {
            $this->assertSame($mensaje, $error->errors()['codigo'][0]);
        }
    }

    public function test_un_codigo_de_otro_pnf_no_se_encuentra(): void
    {
        [$sesion] = $this->sesionDeHoy();
        $codigo = app(CodigoQrDinamico::class)->generar($sesion)['codigo'];
        app(PnfActual::class)->establecer(Pnf::factory()->create());

        $this->expectException(ValidationException::class);

        app(CodigoQrDinamico::class)->resolver($codigo);
    }

    public static function horasDeRegistro(): array
    {
        return [
            '10 minutos antes' => ['07:50', EstadoAsistencia::Presente],
            'dentro de la tolerancia' => ['08:15', EstadoAsistencia::Presente],
            'pasada la tolerancia' => ['08:16', EstadoAsistencia::Tardanza],
        ];
    }

    #[DataProvider('horasDeRegistro')]
    public function test_asigna_presente_o_tardanza_segun_la_hora(string $hora, EstadoAsistencia $esperado): void
    {
        [$sesion, $estudiante] = $this->sesionDeHoy();
        $this->travelTo("2026-10-20 {$hora}:00");

        $resultado = app(RegistrarAsistenciaPorQr::class)->handle($sesion, $estudiante, MetodoAsistencia::QrDinamico);

        $this->assertSame($esperado, $resultado['asistencia']->estado);
        $this->assertSame(MetodoAsistencia::QrDinamico, $resultado['asistencia']->metodo);
        $this->assertSame(EstadoSesion::Realizada, $sesion->fresh()->estado);
    }

    public static function horasFueraDeVentana(): array
    {
        return [
            'muy temprano' => ['2026-10-20 07:40:00'],
            'después de la clase' => ['2026-10-20 10:16:00'],
            'otro día' => ['2026-10-21 08:05:00'],
        ];
    }

    #[DataProvider('horasFueraDeVentana')]
    public function test_rechaza_el_registro_fuera_de_la_clase(string $momento): void
    {
        [$sesion, $estudiante] = $this->sesionDeHoy();
        $this->travelTo($momento);

        $this->expectException(ValidationException::class);

        try {
            app(RegistrarAsistenciaPorQr::class)->handle($sesion, $estudiante, MetodoAsistencia::QrDocente);
        } finally {
            $this->assertSame(0, Asistencia::count());
        }
    }

    public function test_rechaza_a_quien_no_esta_inscrito_en_el_curso(): void
    {
        [$sesion] = $this->sesionDeHoy();
        $ajeno = Estudiante::factory()->for($sesion->curso->pnf)->create(['nombres' => 'Luis', 'apellidos' => 'Gómez']);
        $this->travelTo('2026-10-20 08:00:00');

        try {
            app(RegistrarAsistenciaPorQr::class)->handle($sesion, $ajeno, MetodoAsistencia::QrDocente);
            $this->fail('Se esperaba un error de validación.');
        } catch (ValidationException $error) {
            $this->assertSame('Luis Gómez no está convocado a esta sesión.', $error->errors()['codigo'][0]);
        }
    }

    public function test_con_qr_dinamico_exige_estar_cerca_de_la_sede(): void
    {
        [$sesion, $estudiante] = $this->sesionDeHoy(new Sede(['nombre' => 'Sede Principal', 'latitud' => 8.1222, 'longitud' => -63.5497, 'radio_metros' => 200]));
        $this->travelTo('2026-10-20 08:00:00');
        $registrar = app(RegistrarAsistenciaPorQr::class);

        foreach ([[null, null, 'Activa la ubicación del teléfono para registrar tu asistencia.'], [8.1400, -63.5497, 'Debes estar en la sede Sede Principal para registrar tu asistencia.']] as [$latitud, $longitud, $mensaje]) {
            try {
                $registrar->handle($sesion, $estudiante, MetodoAsistencia::QrDinamico, latitud: $latitud, longitud: $longitud);
                $this->fail('Se esperaba un error de validación.');
            } catch (ValidationException $error) {
                $this->assertSame($mensaje, $error->errors()['latitud'][0]);
            }
        }

        $resultado = $registrar->handle($sesion, $estudiante, MetodoAsistencia::QrDinamico, latitud: 8.1230, longitud: -63.5500);

        $this->assertTrue($resultado['nueva']);
    }

    public function test_el_docente_escanea_el_carnet_sin_validar_ubicacion(): void
    {
        [$sesion, $estudiante] = $this->sesionDeHoy(new Sede(['nombre' => 'Sede Principal', 'latitud' => 8.1222, 'longitud' => -63.5497]));
        $this->travelTo('2026-10-20 08:00:00');

        $resultado = app(RegistrarAsistenciaPorQr::class)->handle($sesion, $estudiante, MetodoAsistencia::QrDocente);

        $this->assertSame(EstadoAsistencia::Presente, $resultado['asistencia']->estado);
    }

    public function test_no_cambia_un_registro_existente_pero_si_corrige_un_ausente(): void
    {
        [$sesion, $estudiante] = $this->sesionDeHoy();
        $companero = Estudiante::factory()->for($sesion->curso->pnf)->create();
        $sesion->curso->estudiantes()->attach($companero);
        $this->travelTo('2026-10-20 08:00:00');
        app(RegistrarAsistencias::class)->handle($sesion, [$estudiante->id => EstadoAsistencia::Justificado, $companero->id => EstadoAsistencia::Ausente], null);
        $registrar = app(RegistrarAsistenciaPorQr::class);

        $justificado = $registrar->handle($sesion, $estudiante, MetodoAsistencia::QrDinamico);
        $corregido = $registrar->handle($sesion, $companero, MetodoAsistencia::QrDinamico);

        $this->assertFalse($justificado['nueva']);
        $this->assertSame(EstadoAsistencia::Justificado, $justificado['asistencia']->estado);
        $this->assertTrue($corregido['nueva']);
        $this->assertSame(EstadoAsistencia::Presente, $corregido['asistencia']->estado);
    }

    public function test_cerrar_marca_ausentes_a_quienes_no_se_registraron(): void
    {
        [$sesion, $estudiante] = $this->sesionDeHoy();
        $faltante = Estudiante::factory()->for($sesion->curso->pnf)->create();
        $sesion->curso->estudiantes()->attach($faltante);
        $this->travelTo('2026-10-20 08:00:00');
        app(RegistrarAsistenciaPorQr::class)->handle($sesion, $estudiante, MetodoAsistencia::QrDinamico);

        $this->assertSame(EstadoAsistencia::Ausente, app(RegistrarAsistencias::class)->estadosIniciales($sesion)[$faltante->id]);

        $ausentes = app(CerrarAsistencia::class)->handle($sesion, User::factory()->create());

        $this->assertSame(1, $ausentes);
        $this->assertSame(EstadoAsistencia::Ausente, $sesion->asistencias()->whereBelongsTo($faltante)->sole()->estado);
        $this->assertSame(EstadoAsistencia::Presente, $sesion->asistencias()->whereBelongsTo($estudiante)->sole()->estado);
    }

    /**
     * Clase de 08:00 a 10:15 el 20/10/2026, con un estudiante inscrito.
     *
     * @return array{0: SesionClase, 1: Estudiante}
     */
    private function sesionDeHoy(?Sede $sede = null): array
    {
        $sede?->save();
        $pnf = Pnf::factory()->create();
        $curso = Curso::factory()->for(Seccion::factory()->for(Trayecto::factory()->for($pnf))->state(['sede_id' => $sede?->id]))->create();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $curso->estudiantes()->attach($estudiante);
        $sesion = SesionClase::factory()->for($curso)->create(['fecha' => '2026-10-20', 'hora_inicio' => '08:00:00', 'hora_fin' => '10:15:00']);
        app(PnfActual::class)->establecer($pnf);

        return [$sesion, $estudiante];
    }
}
