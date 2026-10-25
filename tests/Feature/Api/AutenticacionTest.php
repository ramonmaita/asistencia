<?php

namespace Tests\Feature\Api;

use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_docente_inicia_sesion_y_recibe_un_token(): void
    {
        $docente = User::factory()->docenteDe(Pnf::factory()->create())->create(['email' => 'ana@upt.edu.ve']);

        $respuesta = $this->postJson('/api/v1/docente/login', ['email' => 'ANA@upt.edu.ve', 'password' => 'password', 'dispositivo' => 'Pixel 7']);

        $respuesta->assertCreated()
            ->assertJsonPath('usuario.tipo', 'docente')
            ->assertJsonPath('usuario.id', $docente->id);
        $this->assertTrue($docente->tokens()->sole()->can('docente'));

        $this->withToken($respuesta->json('token'))->getJson('/api/v1/yo')->assertOk()->assertJsonPath('data.email', 'ana@upt.edu.ve');
    }

    public function test_rechaza_credenciales_incorrectas(): void
    {
        User::factory()->docenteDe(Pnf::factory()->create())->create(['email' => 'ana@upt.edu.ve']);

        $this->postJson('/api/v1/docente/login', ['email' => 'ana@upt.edu.ve', 'password' => 'otra', 'dispositivo' => 'Pixel 7'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Las credenciales no son correctas.']);
    }

    public function test_rechaza_al_docente_inactivo_o_sin_pnf(): void
    {
        User::factory()->docenteDe(Pnf::factory()->create())->create(['email' => 'inactivo@upt.edu.ve', 'activo' => false]);
        User::factory()->create(['email' => 'sinpnf@upt.edu.ve']);

        foreach (['inactivo@upt.edu.ve', 'sinpnf@upt.edu.ve'] as $email) {
            $this->postJson('/api/v1/docente/login', ['email' => $email, 'password' => 'password', 'dispositivo' => 'Pixel 7'])
                ->assertJsonValidationErrors(['email' => 'Tu cuenta no tiene acceso a ningún PNF.']);
        }

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_el_estudiante_inicia_sesion_con_su_cedula(): void
    {
        $estudiante = Estudiante::factory()->create(['cedula' => '30111222']);

        $this->postJson('/api/v1/estudiante/login', ['cedula' => 'V-30.111.222', 'password' => 'password', 'dispositivo' => 'Galaxy A14'])
            ->assertCreated()
            ->assertJsonPath('usuario.tipo', 'estudiante')
            ->assertJsonPath('usuario.debe_cambiar_password', false);

        $this->assertTrue($estudiante->tokens()->sole()->can('estudiante'));
    }

    public function test_con_la_clave_inicial_el_estudiante_solo_puede_cambiarla(): void
    {
        $estudiante = Estudiante::factory()->create(['cedula' => '30111222', 'password' => null]);
        Sanctum::actingAs($estudiante, ['estudiante']);

        $this->getJson('/api/v1/estudiante/cursos')->assertForbidden()->assertJsonPath('message', 'Debes cambiar tu contraseña inicial antes de continuar.');

        $this->putJson('/api/v1/estudiante/contrasena', ['contrasena_actual' => '30111222', 'contrasena' => '30111222', 'contrasena_confirmation' => '30111222'])
            ->assertJsonValidationErrors(['contrasena' => 'La nueva contraseña no puede ser tu cédula.']);

        $this->putJson('/api/v1/estudiante/contrasena', ['contrasena_actual' => '30111222', 'contrasena' => 'NuevaClave2026', 'contrasena_confirmation' => 'NuevaClave2026'])
            ->assertOk();

        $this->assertFalse($estudiante->fresh()->debe_cambiar_password);
        $this->getJson('/api/v1/estudiante/cursos')->assertOk();
    }

    public function test_un_token_de_estudiante_no_entra_a_las_rutas_del_docente_ni_viceversa(): void
    {
        $pnf = Pnf::factory()->create();

        Sanctum::actingAs(Estudiante::factory()->for($pnf)->create(), ['estudiante']);
        $this->getJson('/api/v1/docente/pnfs')->assertForbidden();

        Sanctum::actingAs(User::factory()->docenteDe($pnf)->create(), ['docente']);
        $this->getJson('/api/v1/estudiante/cursos')->assertForbidden();
    }

    public function test_sin_token_responde_401(): void
    {
        $this->getJson('/api/v1/yo')->assertUnauthorized();
    }

    public function test_cerrar_sesion_revoca_el_token(): void
    {
        User::factory()->docenteDe(Pnf::factory()->create())->create(['email' => 'ana@upt.edu.ve']);
        $token = $this->postJson('/api/v1/docente/login', ['email' => 'ana@upt.edu.ve', 'password' => 'password', 'dispositivo' => 'Pixel 7'])->json('token');

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_limita_los_intentos_de_inicio_de_sesion(): void
    {
        foreach (range(1, 5) as $intento) {
            $this->postJson('/api/v1/estudiante/login', ['cedula' => '30111222', 'password' => 'mala', 'dispositivo' => 'X']);
        }

        $this->postJson('/api/v1/estudiante/login', ['cedula' => '30111222', 'password' => 'mala', 'dispositivo' => 'X'])->assertTooManyRequests();
    }
}
