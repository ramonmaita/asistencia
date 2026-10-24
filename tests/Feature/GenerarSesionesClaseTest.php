<?php

namespace Tests\Feature;

use App\Actions\GenerarSesionesClase;
use App\Enums\DiaSemana;
use App\Enums\EstadoSesion;
use App\Models\Curso;
use App\Models\Feriado;
use App\Models\Horario;
use App\Models\Periodo;
use App\Models\Seccion;
use App\Models\SesionClase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerarSesionesClaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_una_sesion_por_bloque_del_horario_en_el_periodo(): void
    {
        $curso = $this->cursoConPeriodo('2026-10-05', '2026-10-18');
        Horario::factory()->for($curso)->create(['dia_semana' => DiaSemana::Lunes, 'hora_inicio' => '08:00', 'hora_fin' => '10:15']);
        Horario::factory()->for($curso)->create(['dia_semana' => DiaSemana::Jueves, 'hora_inicio' => '13:00', 'hora_fin' => '14:30']);

        $creadas = app(GenerarSesionesClase::class)->handle($curso);

        $this->assertSame(4, $creadas);
        $this->assertSame(
            ['2026-10-05 08:00:00', '2026-10-08 13:00:00', '2026-10-12 08:00:00', '2026-10-15 13:00:00'],
            $curso->sesiones()->orderBy('fecha')->get()->map(fn (SesionClase $s): string => $s->fecha->toDateString().' '.$s->hora_inicio)->all(),
        );
        $this->assertSame($curso->pnf_id, SesionClase::first()->pnf_id);
        $this->assertSame(EstadoSesion::Programada, SesionClase::first()->estado);
    }

    public function test_salta_los_feriados(): void
    {
        $curso = $this->cursoConPeriodo('2026-10-05', '2026-10-18');
        Horario::factory()->for($curso)->create(['dia_semana' => DiaSemana::Lunes]);
        Feriado::create(['fecha' => '2026-10-12', 'descripcion' => 'Día de la Resistencia Indígena']);

        app(GenerarSesionesClase::class)->handle($curso);

        $this->assertSame(['2026-10-05'], $curso->sesiones()->pluck('fecha')->map->toDateString()->all());
    }

    public function test_no_duplica_ni_modifica_las_sesiones_existentes(): void
    {
        $curso = $this->cursoConPeriodo('2026-10-05', '2026-10-18');
        Horario::factory()->for($curso)->create(['dia_semana' => DiaSemana::Lunes, 'hora_inicio' => '08:00']);
        app(GenerarSesionesClase::class)->handle($curso);
        $curso->sesiones()->first()->update(['estado' => EstadoSesion::Realizada]);

        $creadas = app(GenerarSesionesClase::class)->handle($curso);

        $this->assertSame(0, $creadas);
        $this->assertSame(2, $curso->sesiones()->count());
        $this->assertSame(1, $curso->sesiones()->realizadas()->count());
    }

    public function test_agrega_solo_las_sesiones_de_un_bloque_nuevo(): void
    {
        $curso = $this->cursoConPeriodo('2026-10-05', '2026-10-18');
        Horario::factory()->for($curso)->create(['dia_semana' => DiaSemana::Lunes]);
        app(GenerarSesionesClase::class)->handle($curso);
        Horario::factory()->for($curso)->create(['dia_semana' => DiaSemana::Viernes]);

        $creadas = app(GenerarSesionesClase::class)->handle($curso->fresh());

        $this->assertSame(2, $creadas);
        $this->assertSame(4, $curso->sesiones()->count());
    }

    private function cursoConPeriodo(string $inicio, string $fin): Curso
    {
        $periodo = Periodo::factory()->create(['fecha_inicio' => $inicio, 'fecha_fin' => $fin]);

        return Curso::factory()->create([
            'seccion_id' => Seccion::factory()->for($periodo)->create()->id,
        ]);
    }
}
