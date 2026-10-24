<?php

namespace Tests\Feature;

use App\Enums\NivelInasistencia;
use App\Filament\App\Pages\AuditoriaAsistencias;
use App\Filament\App\Resources\Cursos\Pages\ViewCurso;
use App\Filament\App\Resources\Cursos\RelationManagers\ResumenAsistenciaRelationManager;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Trayecto;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportesAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_resumen_del_curso_muestra_el_porcentaje_de_cada_estudiante(): void
    {
        [$curso, $docente] = $this->cursoConDocente();
        $ana = $this->inscrito($curso, 'Álvarez');
        $luis = $this->inscrito($curso, 'Bravo');
        foreach (['ausente', 'presente', 'presente', 'presente'] as $estado) {
            $sesion = SesionClase::factory()->for($curso)->realizada()->create();
            Asistencia::factory()->for($sesion, 'sesionable')->for($ana)->create(['estado' => $estado]);
            Asistencia::factory()->for($sesion, 'sesionable')->for($luis)->create();
        }

        $this->entrarAlPanel($docente, $curso->pnf);

        Livewire::test(ResumenAsistenciaRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->assertCanSeeTableRecords([$ana, $luis])
            ->assertTableColumnStateSet('porcentaje', '25,00 %', $ana)
            ->assertTableColumnStateSet('porcentaje', '0,00 %', $luis)
            ->assertTableColumnStateSet('nivel', NivelInasistencia::EnRiesgo, $ana);
    }

    public function test_exporta_el_resumen_a_excel_y_pdf(): void
    {
        [$curso, $docente] = $this->cursoConDocente();
        $this->inscrito($curso, 'Álvarez');

        $this->entrarAlPanel($docente, $curso->pnf);

        Livewire::test(ResumenAsistenciaRelationManager::class, ['ownerRecord' => $curso, 'pageClass' => ViewCurso::class])
            ->callAction(TestAction::make('exportarExcel')->table())
            ->assertFileDownloaded(contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->callAction(TestAction::make('exportarPdf')->table())
            ->assertFileDownloaded(contentType: 'application/pdf');
    }

    public function test_el_docente_no_entra_a_la_auditoria(): void
    {
        [$curso, $docente] = $this->cursoConDocente();

        $this->actingAs($docente)
            ->get(AuditoriaAsistencias::getUrl(tenant: $curso->pnf))
            ->assertForbidden();
    }

    public function test_el_coordinador_abre_la_auditoria(): void
    {
        [$curso] = $this->cursoConDocente();

        $this->actingAs(User::factory()->coordinadorDe($curso->pnf)->create())
            ->get(AuditoriaAsistencias::getUrl(tenant: $curso->pnf))
            ->assertOk()
            ->assertSee('Auditoría de asistencias');
    }

    public function test_la_auditoria_filtra_por_seccion_y_fecha_dentro_del_pnf(): void
    {
        [$curso] = $this->cursoConDocente();
        $otraSeccion = Curso::factory()->for(Seccion::factory()->for($curso->seccion->trayecto))->create();
        $buscada = $this->asistenciaEl($curso, '2026-10-05');
        $fueraDeRango = $this->asistenciaEl($curso, '2026-09-28');
        $deOtraSeccion = $this->asistenciaEl($otraSeccion, '2026-10-05');
        [$cursoAjeno] = $this->cursoConDocente();
        $deOtroPnf = $this->asistenciaEl($cursoAjeno, '2026-10-05');

        $this->entrarAlPanel(User::factory()->coordinadorDe($curso->pnf)->create(), $curso->pnf);

        Livewire::test(AuditoriaAsistencias::class)
            ->assertCanNotSeeTableRecords([$deOtroPnf])
            ->filterTable('periodo', null)
            ->filterTable('seccion', $curso->seccion_id)
            ->filterTable('fecha', ['desde' => '2026-10-01', 'hasta' => '2026-10-31'])
            ->assertCanSeeTableRecords([$buscada])
            ->assertCanNotSeeTableRecords([$fueraDeRango, $deOtraSeccion, $deOtroPnf])
            ->callAction(TestAction::make('exportarExcel')->table())
            ->assertFileDownloaded('auditoria-asistencias.xlsx');
    }

    /**
     * @return array{0: Curso, 1: User}
     */
    private function cursoConDocente(): array
    {
        $pnf = Pnf::factory()->create();
        $docente = User::factory()->docenteDe($pnf)->create();
        $curso = Curso::factory()
            ->for(Seccion::factory()->for(Trayecto::factory()->for($pnf)))
            ->hasAttached($docente, relationship: 'docentes')
            ->create();

        return [$curso, $docente];
    }

    private function inscrito(Curso $curso, string $apellidos): Estudiante
    {
        $estudiante = Estudiante::factory()->for($curso->pnf)->create(['apellidos' => $apellidos]);
        $curso->seccion->estudiantes()->attach($estudiante);

        return $estudiante;
    }

    private function asistenciaEl(Curso $curso, string $fecha): Asistencia
    {
        return Asistencia::factory()
            ->for(SesionClase::factory()->for($curso)->realizada()->state(['fecha' => $fecha]), 'sesionable')
            ->create();
    }

    private function entrarAlPanel(User $user, Pnf $pnf): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);
    }
}
