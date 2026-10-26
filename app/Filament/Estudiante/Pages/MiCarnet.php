<?php

namespace App\Filament\Estudiante\Pages;

use App\Models\Estudiante;
use App\Models\Periodo;
use BackedEnum;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Carnet virtual: el mismo QR del carnet impreso, para mostrarlo en el
 * teléfono y que el docente lo lea desde la app o desde el panel web.
 */
class MiCarnet extends Page
{
    protected string $view = 'filament.estudiante.pages.mi-carnet';

    protected static ?string $title = 'Mi carnet';

    protected static ?string $slug = 'mi-carnet';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public function estudiante(): Estudiante
    {
        return Filament::auth()->user()->loadMissing(['pnf', 'secciones.periodo']);
    }

    public function qr(): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => true,
            'svgAddXmlHeader' => false,
            'quietzoneSize' => 2,
        ])))->render($this->estudiante()->contenidoQr());
    }

    public function seccion(): ?string
    {
        return $this->estudiante()->secciones->firstWhere('periodo.activo', true)?->codigo;
    }

    public function periodo(): ?string
    {
        return Periodo::activo()->value('nombre');
    }
}
