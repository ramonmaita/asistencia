<?php

namespace App\Filament\Estudiante\Resources\Justificaciones\Pages;

use App\Filament\Estudiante\Resources\Justificaciones\JustificacionResource;
use Filament\Resources\Pages\ListRecords;

class ListJustificaciones extends ListRecords
{
    protected static string $resource = JustificacionResource::class;

    protected ?string $heading = 'Mis justificaciones';
}
