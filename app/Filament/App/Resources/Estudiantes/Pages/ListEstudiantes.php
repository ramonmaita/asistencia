<?php

namespace App\Filament\App\Resources\Estudiantes\Pages;

use App\Actions\ImportarEstudiantes;
use App\Filament\App\Resources\Estudiantes\EstudianteResource;
use App\Filament\App\Support\ImportacionDeEstudiantes;
use App\Models\Periodo;
use App\Models\Seccion;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ListEstudiantes extends ListRecords
{
    protected static string $resource = EstudianteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                $this->importarAction(),
                ImportacionDeEstudiantes::plantillaAction(),
            ])
                ->label('Importar')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->button()
                ->color('gray'),
            CreateAction::make(),
        ];
    }

    private function importarAction(): Action
    {
        return Action::make('importar')
            ->label('Importar desde Excel o CSV')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->visible(fn (): bool => EstudianteResource::canCreate())
            ->modalDescription(ImportacionDeEstudiantes::DESCRIPCION)
            ->schema([
                ImportacionDeEstudiantes::campoArchivo(),
                Select::make('seccion_id')
                    ->label('Inscribir en la sección (opcional)')
                    ->options(fn (): array => Seccion::query()
                        ->with('trayecto')
                        ->whereHas('periodo', fn (Builder $periodos) => $periodos->where('activo', true))
                        ->orderBy('codigo')
                        ->get()
                        ->mapWithKeys(fn (Seccion $seccion): array => [$seccion->id => "{$seccion->codigo} · {$seccion->trayecto->nombre}"])
                        ->all())
                    ->helperText(fn (): string => 'Secciones del periodo '.(Periodo::activo()->value('nombre') ?? 'activo').'.')
                    ->live(),
                Toggle::make('inscribir_en_cursos')
                    ->label('Inscribir también en todas las unidades curriculares de la sección')
                    ->helperText('Desactívalo si los estudiantes cursarán solo algunas; luego los inscribes curso por curso.')
                    ->default(true)
                    ->visible(fn (Get $get): bool => filled($get('seccion_id'))),
            ])
            ->action(function (array $data, ImportarEstudiantes $importar): void {
                $disco = Storage::disk('local');

                try {
                    $resultado = $importar->handle(
                        $disco->path($data['archivo']),
                        Filament::getTenant(),
                        filled($data['seccion_id']) ? Seccion::findOrFail($data['seccion_id']) : null,
                        (bool) ($data['inscribir_en_cursos'] ?? false),
                    );
                } finally {
                    $disco->delete($data['archivo']);
                }

                ImportacionDeEstudiantes::notificar($resultado, conInscritos: filled($data['seccion_id']));
            });
    }
}
