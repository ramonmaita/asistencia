<?php

namespace Tests\Feature;

use App\Filament\Estudiante\Pages\CambiarContrasena;
use App\Models\Estudiante;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CambiarContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_estudiante_con_clave_inicial_es_enviado_a_cambiarla(): void
    {
        $estudiante = Estudiante::factory()->create(['password' => null]);

        $this->actingAs($estudiante, 'estudiante')
            ->get('/estudiante')
            ->assertRedirect('/estudiante/cambiar-contrasena');

        $this->get('/estudiante/cambiar-contrasena')->assertOk();
    }

    public function test_el_estudiante_que_ya_la_cambio_entra_al_panel(): void
    {
        $this->actingAs(Estudiante::factory()->create(), 'estudiante')
            ->get('/estudiante')
            ->assertOk();
    }

    public function test_guarda_la_nueva_clave_y_libera_el_panel(): void
    {
        $estudiante = Estudiante::factory()->create(['cedula' => '30111222', 'password' => null]);

        $this->entrarAlPanel($estudiante);

        Livewire::test(CambiarContrasena::class)
            ->fillForm([
                'contrasena_actual' => '30111222',
                'contrasena' => 'NuevaClave2026',
                'contrasena_confirmation' => 'NuevaClave2026',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors()
            ->assertRedirect('/estudiante');

        $estudiante->refresh();
        $this->assertFalse($estudiante->debe_cambiar_password);
        $this->assertTrue(Hash::check('NuevaClave2026', $estudiante->password));
    }

    public function test_rechaza_la_cedula_como_nueva_clave(): void
    {
        $estudiante = Estudiante::factory()->create(['cedula' => '30111222', 'password' => 'Clave1234']);

        $this->entrarAlPanel($estudiante);

        Livewire::test(CambiarContrasena::class)
            ->fillForm([
                'contrasena_actual' => 'Clave1234',
                'contrasena' => '30111222',
                'contrasena_confirmation' => '30111222',
            ])
            ->call('guardar')
            ->assertHasFormErrors(['contrasena']);
    }

    public function test_rechaza_una_clave_actual_incorrecta(): void
    {
        $estudiante = Estudiante::factory()->create(['password' => null]);

        $this->entrarAlPanel($estudiante);

        Livewire::test(CambiarContrasena::class)
            ->fillForm([
                'contrasena_actual' => 'equivocada',
                'contrasena' => 'NuevaClave2026',
                'contrasena_confirmation' => 'NuevaClave2026',
            ])
            ->call('guardar')
            ->assertHasFormErrors(['contrasena_actual']);

        $this->assertTrue($estudiante->fresh()->debe_cambiar_password);
    }

    private function entrarAlPanel(Estudiante $estudiante): void
    {
        $this->actingAs($estudiante, 'estudiante');
        Filament::setCurrentPanel('estudiante');
    }
}
