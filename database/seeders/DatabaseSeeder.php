<?php

namespace Database\Seeders;

use App\Enums\RolPnf;
use App\Models\Estudiante;
use App\Models\Feriado;
use App\Models\Periodo;
use App\Models\Pnf;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para desarrollo local.
 *
 * Todas las cuentas usan la contraseña "password" (ver las factories).
 *  - Superadmin:   admin@gestionpnf.test        → /admin y /app
 *  - Coordinador:  coordinador@gestionpnf.test  → /app (PNF Informática)
 *  - Docente:      docente@gestionpnf.test      → /app (Informática y Administración)
 *  - Estudiante:   cédula 30111222              → /estudiante (Informática)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Sede::create(['nombre' => 'Sede Principal']);

        Periodo::create([
            'nombre' => '2026-II',
            'fecha_inicio' => '2026-09-14',
            'fecha_fin' => '2027-02-12',
            'activo' => true,
        ]);

        foreach ([
            ['2026-10-12', 'Día de la Resistencia Indígena'],
            ['2026-12-24', 'Nochebuena'],
            ['2026-12-25', 'Navidad'],
            ['2026-12-31', 'Fin de año'],
            ['2027-01-01', 'Año nuevo'],
        ] as [$fecha, $descripcion]) {
            Feriado::create(['fecha' => $fecha, 'descripcion' => $descripcion]);
        }

        $informatica = Pnf::create(['nombre' => 'PNF en Informática', 'codigo' => 'PNFI']);
        $administracion = Pnf::create(['nombre' => 'PNF en Administración', 'codigo' => 'PNFA']);

        User::factory()->superadmin()->create([
            'name' => 'Administrador',
            'email' => 'admin@gestionpnf.test',
        ]);

        $coordinador = User::factory()->create([
            'name' => 'Coordinador Informática',
            'email' => 'coordinador@gestionpnf.test',
        ]);
        $coordinador->pnfs()->attach($informatica, ['rol' => RolPnf::Coordinador]);

        $docente = User::factory()->create([
            'name' => 'Docente Compartido',
            'email' => 'docente@gestionpnf.test',
        ]);
        $docente->pnfs()->attach($informatica, ['rol' => RolPnf::Docente]);
        $docente->pnfs()->attach($administracion, ['rol' => RolPnf::Docente]);

        Estudiante::factory()->for($informatica)->create([
            'cedula' => '30111222',
            'nombres' => 'María',
            'apellidos' => 'Pérez',
        ]);
        Estudiante::factory()->count(20)->for($informatica)->create();
        Estudiante::factory()->count(15)->for($administracion)->create();
    }
}
