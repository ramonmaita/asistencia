<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\Filament\EstudiantePanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
    EstudiantePanelProvider::class,
];
