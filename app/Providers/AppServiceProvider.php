<?php

namespace App\Providers;

use App\Models\Estudiante;
use App\Models\SesionClase;
use App\Models\Tutoria;
use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(PnfActual::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Alias cortos en las columnas polimórficas (asistencias y notificaciones).
        Relation::enforceMorphMap([
            'sesion_clase' => SesionClase::class,
            'tutoria' => Tutoria::class,
            'user' => User::class,
            'estudiante' => Estudiante::class,
        ]);
    }
}
