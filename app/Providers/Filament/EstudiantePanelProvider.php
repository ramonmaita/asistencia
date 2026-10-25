<?php

namespace App\Providers\Filament;

use App\Filament\Estudiante\Pages\Auth\Login;
use App\Http\Middleware\EstablecerPnfDelEstudiante;
use App\Http\Middleware\ExigirCambioDeContrasena;
use App\Support\Marca;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel del estudiante (solo lectura): histórico de asistencias,
 * justificaciones y su proyecto. Usa el guard "estudiante".
 */
class EstudiantePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('estudiante')
            ->path('estudiante')
            ->authGuard('estudiante')
            ->login(Login::class)
            ->brandName('Gestión PNF · Estudiantes')
            ->brandLogo(fn () => view('filament.logo', ['subtitulo' => 'Estudiantes']))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/favicon-upt.png'))
            ->font('Source Sans 3')
            ->viteTheme('resources/css/filament/estudiante/theme.css')
            ->topNavigation()
            ->databaseNotifications()
            ->colors([
                'primary' => Marca::TURQUESA,
                'warning' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Estudiante/Resources'), for: 'App\Filament\Estudiante\Resources')
            ->discoverPages(in: app_path('Filament/Estudiante/Pages'), for: 'App\Filament\Estudiante\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Estudiante/Widgets'), for: 'App\Filament\Estudiante\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EstablecerPnfDelEstudiante::class,
                ExigirCambioDeContrasena::class,
            ]);
    }
}
