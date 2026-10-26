<?php

namespace Tests\Feature;

use App\Enums\RolPnf;
use App\Filament\Admin\Resources\Pnfs\Pages\EditPnf;
use App\Filament\Admin\Resources\Pnfs\RelationManagers\UsersRelationManager;
use App\Models\Pnf;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelAdministracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_superadmin_puede_abrir_todas_las_pantallas(): void
    {
        $pnf = Pnf::factory()->create();
        User::factory()->create()->pnfs()->attach($pnf, ['rol' => RolPnf::Coordinador]);

        $this->actingAs(User::factory()->superadmin()->create());

        foreach (['pnfs', 'users', 'estudiantes', 'periodos', 'feriados', 'sedes'] as $recurso) {
            $this->get("/admin/{$recurso}")->assertOk();
            $this->get("/admin/{$recurso}/create")->assertOk();
        }

        $this->get("/admin/pnfs/{$pnf->id}/edit")->assertOk();
    }

    public function test_el_gestor_de_miembros_lista_solo_a_los_del_pnf(): void
    {
        $pnf = Pnf::factory()->create();
        $miembro = User::factory()->create();
        $miembro->pnfs()->attach($pnf, ['rol' => RolPnf::Docente]);
        $ajeno = User::factory()->create();

        $this->actingAs(User::factory()->superadmin()->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(UsersRelationManager::class, [
            'ownerRecord' => $pnf,
            'pageClass' => EditPnf::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$miembro])
            ->assertCanNotSeeTableRecords([$ajeno]);
    }

    public function test_el_slug_del_pnf_se_genera_desde_el_codigo(): void
    {
        $pnf = Pnf::create(['nombre' => 'PNF en Informática', 'codigo' => 'PNFI']);

        $this->assertSame('pnfi', $pnf->slug);
    }
}
