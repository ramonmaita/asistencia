<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Cursos\Pages\ViewCurso;
use App\Filament\App\Resources\Cursos\RelationManagers\ResumenAsistenciaRelationManager;
use App\Filament\App\Resources\Estudiantes\Pages\EditEstudiante;
use App\Filament\App\Resources\Estudiantes\Pages\ListEstudiantes;
use App\Filament\App\Resources\Estudiantes\RelationManagers\CursosRelationManager;
use App\Filament\App\Resources\Seccions\Pages\EditSeccion;
use App\Filament\App\Resources\Seccions\RelationManagers\EstudiantesRelationManager;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Periodo;
use App\Models\Pnf;
use App\Models\Seccion;
use App\Models\Trayecto;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Los estudiantes se inscriben en cada unidad curricular: dos de la misma
 * sección pueden cursar unidades distintas.
 */
class InscripcionEnCursosTest extends TestCase
{
    use RefreshDatabase;

    public function test_al_inscribir_en_la_seccion_se_inscribe_en_todas_sus_unidades(): void
    {
        [$pnf, $seccion, $algoritmica, $matematica] = $this->seccionConDosCursos();
        $estudiante = Estudiante::factory()->for($pnf)->create();

        $this->entrarComoCoordinador($pnf);

        Livewire::test(EstudiantesRelationManager::class, ['ownerRecord' => $seccion, 'pageClass' => EditSeccion::class])
            ->callAction(TestAction::make('attach')->table(), data: ['recordId' => [$estudiante->id], 'inscribir_en_cursos' => true])
            ->assertHasNoFormErrors();

        $this->assertTrue($algoritmica->estudiantes->contains($estudiante));
        $this->assertTrue($matematica->estudiantes->contains($estudiante));
    }

    public function test_puede_inscribir_en_la_seccion_sin_inscribir_en_las_unidades(): void
    {
        [$pnf, $seccion, $algoritmica] = $this->seccionConDosCursos();
        $estudiante = Estudiante::factory()->for($pnf)->create();

        $this->entrarComoCoordinador($pnf);

        Livewire::test(EstudiantesRelationManager::class, ['ownerRecord' => $seccion, 'pageClass' => EditSeccion::class])
            ->callAction(TestAction::make('attach')->table(), data: ['recordId' => [$estudiante->id], 'inscribir_en_cursos' => false]);

        $this->assertTrue($seccion->estudiantes->contains($estudiante));
        $this->assertSame(0, $algoritmica->estudiantes()->count());
    }

    public function test_al_retirar_de_la_seccion_sale_de_sus_unidades(): void
    {
        [$pnf, $seccion, $algoritmica, $matematica] = $this->seccionConDosCursos();
        $estudiante = Estudiante::factory()->for($pnf)->create();
        $seccion->estudiantes()->attach($estudiante);
        $algoritmica->estudiantes()->attach($estudiante);
        $deOtraSeccion = Curso::factory()->for(Seccion::factory()->for($seccion->trayecto))->create();
        $deOtraSeccion->estudiantes()->attach($estudiante);

        $this->entrarComoCoordinador($pnf);

        Livewire::test(EstudiantesRelationManager::class, ['ownerRecord' => $seccion, 'pageClass' => EditSeccion::class])
            ->callAction(TestAction::make('detach')->table($estudiante));

        $this->assertSame(0, $algoritmica->estudiantes()->count());
        $this->assertTrue($deOtraSeccion->estudiantes->contains($estudiante));
    }

    public function test_el_coordinador_inscribe_a_toda_la_seccion_en_el_curso(): void
    {
        [$pnf, $seccion, $algoritmica] = $this->seccionConDosCursos();
        $estudiantes = Estudiante::factory()->count(3)->for($pnf)->create();
        $seccion->estudiantes()->attach($estudiantes);
        $algoritmica->estudiantes()->attach($estudiantes->first());

        $this->entrarComoCoordinador($pnf);

        Livewire::test(ResumenAsistenciaRelationManager::class, ['ownerRecord' => $algoritmica, 'pageClass' => ViewCurso::class])
            ->callAction(TestAction::make('inscribirSeccion')->table())
            ->assertNotified('Se inscribieron 2 estudiantes');

        $this->assertSame(3, $algoritmica->estudiantes()->count());
    }

    public function test_el_coordinador_inscribe_en_el_curso_a_un_estudiante_de_otra_seccion(): void
    {
        [$pnf, , $algoritmica] = $this->seccionConDosCursos();
        $repitiente = Estudiante::factory()->for($pnf)->create();

        $this->entrarComoCoordinador($pnf);

        Livewire::test(ResumenAsistenciaRelationManager::class, ['ownerRecord' => $algoritmica, 'pageClass' => ViewCurso::class])
            ->callAction(TestAction::make('attach')->table(), data: ['recordId' => [$repitiente->id]])
            ->assertHasNoFormErrors();

        $this->assertTrue($algoritmica->estudiantes->contains($repitiente));
    }

    public function test_el_docente_no_gestiona_la_inscripcion(): void
    {
        [$pnf, , $algoritmica] = $this->seccionConDosCursos();
        $docente = User::factory()->docenteDe($pnf)->create();
        $algoritmica->docentes()->attach($docente);

        $this->entrar($docente, $pnf);

        Livewire::test(ResumenAsistenciaRelationManager::class, ['ownerRecord' => $algoritmica, 'pageClass' => ViewCurso::class])
            ->assertActionHidden(TestAction::make('attach')->table())
            ->assertActionHidden(TestAction::make('inscribirSeccion')->table());
    }

    public function test_desde_el_estudiante_se_inscribe_en_una_unidad_del_periodo_activo(): void
    {
        [$pnf, , $algoritmica, $matematica] = $this->seccionConDosCursos();
        $estudiante = Estudiante::factory()->for($pnf)->create();

        $this->entrarComoCoordinador($pnf);

        Livewire::test(CursosRelationManager::class, ['ownerRecord' => $estudiante, 'pageClass' => EditEstudiante::class])
            ->callAction(TestAction::make('attach')->table(), data: ['recordId' => [$matematica->id]])
            ->assertHasNoFormErrors();

        $this->assertSame([$matematica->id], $estudiante->cursos()->pluck('cursos.id')->all());
        $this->assertFalse($algoritmica->estudiantes->contains($estudiante));
    }

    public function test_la_importacion_puede_inscribir_en_las_unidades_de_la_seccion(): void
    {
        [$pnf, $seccion, $algoritmica, $matematica] = $this->seccionConDosCursos();
        $archivo = UploadedFile::fake()->createWithContent('lista.csv', "cedula;nombres;apellidos\n30111222;María;Pérez\n");

        $this->entrarComoCoordinador($pnf);

        Livewire::test(ListEstudiantes::class)
            ->callAction('importar', data: ['archivo' => $archivo, 'seccion_id' => $seccion->id, 'inscribir_en_cursos' => true])
            ->assertHasNoFormErrors();

        $maria = Estudiante::sole();
        $this->assertTrue($algoritmica->estudiantes->contains($maria));
        $this->assertTrue($matematica->estudiantes->contains($maria));
    }

    /**
     * @return array{0: Pnf, 1: Seccion, 2: Curso, 3: Curso}
     */
    private function seccionConDosCursos(): array
    {
        $pnf = Pnf::factory()->create();
        $seccion = Seccion::factory()->for(Trayecto::factory()->for($pnf))->for(Periodo::factory()->activo())->create();

        return [$pnf, $seccion, Curso::factory()->for($seccion)->create(), Curso::factory()->for($seccion)->create()];
    }

    private function entrarComoCoordinador(Pnf $pnf): void
    {
        $this->entrar(User::factory()->coordinadorDe($pnf)->create(), $pnf);
    }

    private function entrar(User $user, Pnf $pnf): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);
    }
}
