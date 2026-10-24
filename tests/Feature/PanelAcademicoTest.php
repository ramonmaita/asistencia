<?php

namespace Tests\Feature;

use App\Enums\ModalidadProyecto;
use App\Filament\App\Resources\Cursos\Pages\CreateCurso;
use App\Filament\App\Resources\Cursos\Pages\ListCursos;
use App\Filament\App\Resources\Estudiantes\Pages\ListEstudiantes;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\Trayecto;
use App\Models\UnidadCurricular;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelAcademicoTest extends TestCase
{
    use RefreshDatabase;

    public static function pantallasDeCoordinacion(): array
    {
        return [
            'trayectos' => ['trayectos'],
            'unidades curriculares' => ['unidades-curriculares'],
            'secciones' => ['secciones'],
            'estudiantes' => ['estudiantes'],
        ];
    }

    #[DataProvider('pantallasDeCoordinacion')]
    public function test_el_coordinador_abre_las_pantallas_de_su_pnf(string $recurso): void
    {
        $pnf = Pnf::factory()->create();

        $this->actingAs(User::factory()->coordinadorDe($pnf)->create())
            ->get("/app/{$pnf->slug}/{$recurso}")
            ->assertOk();

        $this->get("/app/{$pnf->slug}/{$recurso}/create")->assertOk();
    }

    #[DataProvider('pantallasDeCoordinacion')]
    public function test_el_docente_no_puede_gestionar_el_pnf(string $recurso): void
    {
        $pnf = Pnf::factory()->create();

        $this->actingAs(User::factory()->docenteDe($pnf)->create())
            ->get("/app/{$pnf->slug}/{$recurso}")
            ->assertForbidden();
    }

    public function test_el_coordinador_no_ve_secciones_de_otro_pnf(): void
    {
        $pnf = Pnf::factory()->create();
        $seccionAjena = Seccion::factory()->create();

        $this->actingAs(User::factory()->coordinadorDe($pnf)->create())
            ->get("/app/{$pnf->slug}/secciones/{$seccionAjena->id}/edit")
            ->assertNotFound();
    }

    public function test_el_docente_solo_ve_los_cursos_que_dicta(): void
    {
        $pnf = Pnf::factory()->create();
        $docente = User::factory()->docenteDe($pnf)->create();
        $seccion = Seccion::factory()->for(Trayecto::factory()->for($pnf))->create();
        $suyo = Curso::factory()->for($seccion)->hasAttached($docente, relationship: 'docentes')->create();
        $deOtro = Curso::factory()->for($seccion)->create();

        $this->entrarAlPanel($docente, $pnf);

        Livewire::test(ListCursos::class)
            ->assertCanSeeTableRecords([$suyo])
            ->assertCanNotSeeTableRecords([$deOtro]);

        $this->get("/app/{$pnf->slug}/cursos/{$deOtro->id}")->assertNotFound();
        $this->get("/app/{$pnf->slug}/cursos/{$suyo->id}")->assertOk();
        $this->get("/app/{$pnf->slug}/cursos/{$suyo->id}/edit")->assertForbidden();
    }

    public function test_crea_un_curso_con_docentes_y_horario(): void
    {
        $pnf = Pnf::factory()->create();
        $docente = User::factory()->docenteDe($pnf)->create();
        $seccion = Seccion::factory()->for(Trayecto::factory()->for($pnf))->create();
        $unidad = UnidadCurricular::factory()->create(['trayecto_id' => $seccion->trayecto_id]);

        $this->entrarAlPanel(User::factory()->coordinadorDe($pnf)->create(), $pnf);

        Livewire::test(CreateCurso::class)
            ->fillForm([
                'seccion_id' => $seccion->id,
                'unidad_curricular_id' => $unidad->id,
                'docentes' => [$docente->id],
                'horarios' => [
                    ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:15', 'aula' => 'A1'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $curso = Curso::sole();
        $this->assertSame($pnf->id, $curso->pnf_id);
        $this->assertTrue($curso->docentes->contains($docente));
        $this->assertSame(1, $curso->horarios()->count());
    }

    public function test_rechaza_una_unidad_curricular_de_otro_trayecto(): void
    {
        $pnf = Pnf::factory()->create();
        $seccion = Seccion::factory()->for(Trayecto::factory()->for($pnf)->state(['numero' => 1]))->create();
        $deOtroTrayecto = UnidadCurricular::factory()->for(Trayecto::factory()->for($pnf)->state(['numero' => 2]))->create();

        $this->entrarAlPanel(User::factory()->coordinadorDe($pnf)->create(), $pnf);

        Livewire::test(CreateCurso::class)
            ->fillForm(['seccion_id' => $seccion->id, 'unidad_curricular_id' => $deOtroTrayecto->id])
            ->call('create')
            ->assertHasFormErrors(['unidad_curricular_id']);

        $this->assertSame(0, Curso::count());
    }

    public function test_el_trayecto_individual_fija_un_integrante(): void
    {
        $trayecto = Trayecto::factory()->individual()->create(['min_integrantes' => 3, 'max_integrantes' => 5]);

        $this->assertSame(ModalidadProyecto::Individual, $trayecto->modalidad_proyecto);
        $this->assertSame([1, 1], [$trayecto->min_integrantes, $trayecto->max_integrantes]);
    }

    public function test_importa_estudiantes_desde_la_lista(): void
    {
        $pnf = Pnf::factory()->create();
        $archivo = UploadedFile::fake()->createWithContent('lista.csv', "cedula;nombres;apellidos\n30111222;María;Pérez\n");

        $this->entrarAlPanel(User::factory()->coordinadorDe($pnf)->create(), $pnf);

        Livewire::test(ListEstudiantes::class)
            ->callAction('importar', data: ['archivo' => $archivo])
            ->assertHasNoFormErrors()
            ->assertNotified('Importación completada');

        $this->assertSame($pnf->id, Estudiante::sole()->pnf_id);
    }

    public function test_descarga_los_carnets_en_pdf(): void
    {
        $pnf = Pnf::factory()->create();
        $estudiantes = Estudiante::factory()->count(3)->for($pnf)->create();

        $this->entrarAlPanel(User::factory()->coordinadorDe($pnf)->create(), $pnf);

        Livewire::test(ListEstudiantes::class)
            ->callTableBulkAction('imprimirCarnets', $estudiantes)
            ->assertFileDownloaded('carnets.pdf');
    }

    private function entrarAlPanel(User $user, Pnf $pnf): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);
    }
}
