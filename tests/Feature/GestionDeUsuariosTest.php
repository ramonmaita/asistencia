<?php

namespace Tests\Feature;

use App\Enums\RolPnf;
use App\Filament\Admin\Resources\Estudiantes\Pages\EditEstudiante;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\App\Resources\Personal\Pages\CreatePersonal;
use App\Filament\App\Resources\Personal\Pages\ListPersonal;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionDeUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_superadmin_filtra_al_personal_por_rol(): void
    {
        $pnf = Pnf::factory()->create();
        $administrativo = User::factory()->administrativoDe($pnf)->create();
        $docente = User::factory()->docenteDe($pnf)->create();

        $this->actingAs(User::factory()->superadmin()->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(ListUsers::class)
            ->filterTable('rol', RolPnf::Administrativo->value)
            ->assertCanSeeTableRecords([$administrativo])
            ->assertCanNotSeeTableRecords([$docente]);
    }

    public function test_el_superadmin_cambia_de_pnf_a_un_estudiante(): void
    {
        $estudiante = Estudiante::factory()->for(Pnf::factory())->create();
        $otroPnf = Pnf::factory()->create();

        $this->actingAs(User::factory()->superadmin()->create());
        Filament::setCurrentPanel('admin');

        $this->get('/admin/estudiantes')->assertOk()->assertSee($estudiante->apellidos);

        Livewire::test(EditEstudiante::class, ['record' => $estudiante->id])
            ->fillForm(['pnf_id' => $otroPnf->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($otroPnf->id, $estudiante->fresh()->pnf_id);
    }

    public function test_el_coordinador_crea_una_cuenta_con_su_rol_en_el_pnf(): void
    {
        [$pnf, $coordinador] = $this->pnfConCoordinador();
        $this->entrarAlPanel($coordinador, $pnf);

        Livewire::test(CreatePersonal::class)
            ->fillForm([
                'name' => 'Ana Pérez',
                'cedula' => '15000111',
                'email' => 'ana@upt.test',
                'password' => 'secreta123',
                'rol' => RolPnf::Administrativo->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(RolPnf::Administrativo, User::firstWhere('cedula', '15000111')->rolEn($pnf));
    }

    public function test_el_administrativo_no_puede_nombrar_coordinadores(): void
    {
        $pnf = Pnf::factory()->create();
        $this->entrarAlPanel(User::factory()->administrativoDe($pnf)->create(), $pnf);

        Livewire::test(CreatePersonal::class)
            ->fillForm([
                'name' => 'Luis Gómez',
                'cedula' => '16000222',
                'email' => 'luis@upt.test',
                'password' => 'secreta123',
                'rol' => RolPnf::Coordinador->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['rol']);

        $this->assertDatabaseMissing('users', ['cedula' => '16000222']);
    }

    public function test_agrega_al_pnf_a_un_docente_que_ya_tiene_cuenta(): void
    {
        [$pnf, $coordinador] = $this->pnfConCoordinador();
        $deOtroPnf = User::factory()->docenteDe(Pnf::factory()->create())->create();
        $this->entrarAlPanel($coordinador, $pnf);

        Livewire::test(ListPersonal::class)
            ->assertCanNotSeeTableRecords([$deOtroPnf])
            ->callAction('agregarExistente', ['user_id' => $deOtroPnf->id, 'rol' => RolPnf::Docente->value])
            ->assertHasNoActionErrors();

        $this->assertSame(RolPnf::Docente, $deOtroPnf->fresh()->rolEn($pnf));
    }

    public function test_quitar_del_pnf_conserva_la_cuenta(): void
    {
        [$pnf, $coordinador] = $this->pnfConCoordinador();
        $docente = User::factory()->docenteDe($pnf)->create();
        $this->entrarAlPanel($coordinador, $pnf);

        Livewire::test(ListPersonal::class)
            ->callTableAction('quitar', $docente);

        $this->assertNull($docente->fresh()->rolEn($pnf));
        $this->assertModelExists($docente);
    }

    public function test_el_administrativo_no_cambia_a_un_coordinador_ni_a_si_mismo(): void
    {
        [$pnf, $coordinador] = $this->pnfConCoordinador();
        $administrativo = User::factory()->administrativoDe($pnf)->create();
        $docente = User::factory()->docenteDe($pnf)->create();
        $this->entrarAlPanel($administrativo, $pnf);

        $this->assertFalse($administrativo->can('asignarRol', $coordinador));
        $this->assertFalse($administrativo->can('asignarRol', $administrativo));
        $this->assertTrue($administrativo->can('asignarRol', $docente));
    }

    public function test_solo_edita_los_datos_de_quien_trabaja_unicamente_en_el_pnf(): void
    {
        [$pnf, $coordinador] = $this->pnfConCoordinador();
        $soloDeEstePnf = User::factory()->docenteDe($pnf)->create();
        $compartido = User::factory()->docenteDe($pnf)->create();
        $compartido->pnfs()->attach(Pnf::factory()->create(), ['rol' => RolPnf::Docente]);
        $this->entrarAlPanel($coordinador, $pnf);

        $this->assertTrue($coordinador->can('update', $soloDeEstePnf));
        $this->assertFalse($coordinador->can('update', $compartido));
        $this->assertTrue($coordinador->can('asignarRol', $compartido));
    }

    public function test_el_docente_no_gestiona_al_personal(): void
    {
        $pnf = Pnf::factory()->create();

        $this->actingAs(User::factory()->docenteDe($pnf)->create())
            ->get("/app/{$pnf->slug}/personal")
            ->assertForbidden();
    }

    public function test_una_cuenta_desactivada_no_entra_al_panel(): void
    {
        [$pnf, $coordinador] = $this->pnfConCoordinador();
        $docente = User::factory()->docenteDe($pnf)->create();
        $this->entrarAlPanel($coordinador, $pnf);

        Livewire::test(ListPersonal::class)
            ->callTableAction('activarODesactivar', $docente);

        $this->assertFalse($docente->fresh()->activo);
        $this->assertFalse($docente->fresh()->canAccessPanel(Filament::getPanel('app')));
    }

    /**
     * @return array{0: Pnf, 1: User}
     */
    private function pnfConCoordinador(): array
    {
        $pnf = Pnf::factory()->create();

        return [$pnf, User::factory()->coordinadorDe($pnf)->create()];
    }

    private function entrarAlPanel(User $usuario, Pnf $pnf): void
    {
        $this->actingAs($usuario);
        Filament::setCurrentPanel('app');
        Filament::setTenant($pnf);
        app(PnfActual::class)->establecer($pnf);
    }
}
