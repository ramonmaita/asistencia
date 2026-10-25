<?php

namespace App\Providers;

use App\Models\Estudiante;
use App\Models\SesionClase;
use App\Models\Tutoria;
use App\Models\User;
use App\Support\PnfActual;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // Inicio de sesión de la app móvil: 5 intentos por minuto por cuenta e IP.
        RateLimiter::for('login-api', fn (Request $request): Limit => Limit::perMinute(5)->by(
            Str::lower((string) ($request->input('email') ?? $request->input('cedula'))).'|'.$request->ip(),
        ));

        // Alias cortos en las columnas polimórficas (asistencias y notificaciones).
        Relation::enforceMorphMap([
            'sesion_clase' => SesionClase::class,
            'tutoria' => Tutoria::class,
            'user' => User::class,
            'estudiante' => Estudiante::class,
        ]);
    }
}
