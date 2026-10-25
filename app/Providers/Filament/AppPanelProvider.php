<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EstablecerPnfDelPanel;
use App\Models\Pnf;
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
 * Panel académico (coordinadores y docentes). Cada PNF es un tenant;
 * el docente que trabaja en varios PNF cambia entre ellos desde el menú.
 */
class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->brandName('Gestión PNF')
            ->brandLogo(fn () => view('filament.logo', ['subtitulo' => 'Académico']))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/favicon.svg'))
            ->font('Source Sans 3')
            ->viteTheme('resources/css/filament/institucional/theme.css')
            ->topbar(false)
            ->tenant(Pnf::class, slugAttribute: 'slug')
            ->tenantMiddleware([
                EstablecerPnfDelPanel::class,
            ], isPersistent: true)
            ->databaseNotifications()
            ->colors([
                'primary' => Marca::MARINO,
                'warning' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
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
            ]);
    }
}
