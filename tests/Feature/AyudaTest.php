<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Ayuda as AyudaAdministracion;
use App\Filament\App\Pages\Ayuda;
use App\Filament\Estudiante\Pages\Ayuda as AyudaEstudiante;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AyudaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_coordinador_ve_su_guia_y_la_del_docente(): void
    {
        $pnf = Pnf::factory()->create();

        $this->actingAs(User::factory()->coordinadorDe($pnf)->create())
            ->get(Ayuda::getUrl(tenant: $pnf))
            ->assertOk()
            ->assertSee('Generar las sesiones del periodo')
            ->assertSee('Tomar la asistencia')
            ->assertDontSee('Qué hace el administrativo');
    }

    public function test_cada_rol_del_pnf_ve_solo_su_guia(): void
    {
        $pnf = Pnf::factory()->create();

        $this->actingAs(User::factory()->docenteDe($pnf)->create())
            ->get(Ayuda::getUrl(tenant: $pnf))
            ->assertOk()
            ->assertSee('Tomar la asistencia')
            ->assertDontSee('Generar las sesiones del periodo');

        $this->actingAs(User::factory()->administrativoDe($pnf)->create())
            ->get(Ayuda::getUrl(tenant: $pnf))
            ->assertOk()
            ->assertSee('Qué hace el administrativo')
            ->assertDontSee('Tomar la asistencia');
    }

    public function test_el_estudiante_ve_su_guia(): void
    {
        $this->actingAs(Estudiante::factory()->for(Pnf::factory())->create(), 'estudiante')
            ->get(AyudaEstudiante::getUrl(panel: 'estudiante'))
            ->assertOk()
            ->assertSee('Tu carnet virtual')
            ->assertDontSee('Gestionar al personal');
    }

    public function test_el_administrador_descarga_el_manual_completo_en_pdf(): void
    {
        $this->actingAs(User::factory()->superadmin()->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(AyudaAdministracion::class)
            ->assertSee('Gestionar al personal')
            ->callAction('descargarManual')
            ->assertFileDownloaded('manual-gestion-pnf.pdf', contentType: 'application/pdf');
    }
}
