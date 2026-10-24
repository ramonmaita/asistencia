<?php

namespace Tests\Feature;

use App\Enums\RolPnf;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un usuario de un PNF nunca debe ver ni tocar datos de otro PNF.
 */
class AislamientoPnfTest extends TestCase
{
    use RefreshDatabase;

    private Pnf $informatica;

    private Pnf $administracion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->informatica = Pnf::factory()->create(['codigo' => 'PNFI']);
        $this->administracion = Pnf::factory()->create(['codigo' => 'PNFA']);

        Estudiante::factory()->count(3)->for($this->informatica)->create();
        Estudiante::factory()->count(2)->for($this->administracion)->create();
    }

    public function test_el_scope_filtra_por_el_pnf_actual(): void
    {
        app(PnfActual::class)->establecer($this->informatica);

        $this->assertSame(3, Estudiante::count());
        $this->assertTrue(Estudiante::all()->every(fn ($e) => $e->pnf_id === $this->informatica->id));
    }

    public function test_no_se_puede_obtener_un_registro_de_otro_pnf_por_id(): void
    {
        $ajeno = Estudiante::withoutGlobalScopes()->where('pnf_id', $this->administracion->id)->first();

        app(PnfActual::class)->establecer($this->informatica);

        $this->assertNull(Estudiante::find($ajeno->id));
    }

    public function test_al_crear_se_asigna_el_pnf_actual(): void
    {
        app(PnfActual::class)->establecer($this->administracion);

        $estudiante = Estudiante::factory()->create(['pnf_id' => null]);

        $this->assertSame($this->administracion->id, $estudiante->pnf_id);
        $this->assertNotEmpty($estudiante->qr_token);
    }

    public function test_el_docente_solo_accede_a_los_pnf_asignados(): void
    {
        $docente = User::factory()->create();
        $docente->pnfs()->attach($this->informatica, ['rol' => RolPnf::Docente]);

        $this->actingAs($docente)
            ->get("/app/{$this->informatica->slug}")
            ->assertOk();

        $this->actingAs($docente)
            ->get("/app/{$this->administracion->slug}")
            ->assertNotFound();
    }

    public function test_el_docente_de_varios_pnf_puede_entrar_a_cada_uno(): void
    {
        $docente = User::factory()->create();
        $docente->pnfs()->attach($this->informatica, ['rol' => RolPnf::Docente]);
        $docente->pnfs()->attach($this->administracion, ['rol' => RolPnf::Coordinador]);

        $this->actingAs($docente)->get("/app/{$this->informatica->slug}")->assertOk();
        $this->actingAs($docente)->get("/app/{$this->administracion->slug}")->assertOk();

        $this->assertSame(RolPnf::Docente, $docente->rolEn($this->informatica));
        $this->assertTrue($docente->esCoordinadorDe($this->administracion));
        $this->assertFalse($docente->esCoordinadorDe($this->informatica));
    }

    public function test_solo_el_superadmin_entra_al_panel_de_administracion(): void
    {
        $docente = User::factory()->create();
        $docente->pnfs()->attach($this->informatica, ['rol' => RolPnf::Coordinador]);

        $this->actingAs($docente)->get('/admin')->assertForbidden();

        $this->actingAs(User::factory()->superadmin()->create())
            ->get('/admin')
            ->assertOk();
    }

    public function test_un_usuario_inactivo_no_entra_a_ningun_panel(): void
    {
        $docente = User::factory()->create(['activo' => false]);
        $docente->pnfs()->attach($this->informatica, ['rol' => RolPnf::Docente]);

        $this->actingAs($docente)->get("/app/{$this->informatica->slug}")->assertForbidden();
    }

    public function test_el_estudiante_entra_solo_a_su_panel(): void
    {
        $estudiante = Estudiante::withoutGlobalScopes()->where('pnf_id', $this->informatica->id)->first();

        $this->actingAs($estudiante, 'estudiante')->get('/estudiante')->assertOk();

        // Su sesión no le da acceso a los paneles del personal.
        $this->get('/app')->assertRedirect('/app/login');
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_en_el_panel_del_estudiante_solo_ve_su_pnf(): void
    {
        $estudiante = Estudiante::withoutGlobalScopes()->where('pnf_id', $this->informatica->id)->first();

        $this->actingAs($estudiante, 'estudiante')->get('/estudiante')->assertOk();

        $this->assertSame($this->informatica->id, app(PnfActual::class)->id());
        $this->assertSame(3, Estudiante::count());
    }
}
