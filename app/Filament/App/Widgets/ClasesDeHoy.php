<?php

namespace App\Filament\App\Widgets;

use App\Enums\RolPnf;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Models\SesionClase;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Acceso directo al registro de asistencia de las clases de hoy del docente
 * (el coordinador ve las de todo el PNF).
 */
class ClasesDeHoy extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Clases de hoy';

    /**
     * El administrativo no dicta clases.
     */
    public static function canView(): bool
    {
        return auth()->user()?->rolEnPnfActual() !== RolPnf::Administrativo;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SesionClase::query()
                ->whereDate('fecha', today())
                ->whereHas('curso', fn (Builder $cursos) => $cursos->when(
                    ! auth()->user()->coordinaPnfActual(),
                    fn (Builder $cursos) => $cursos->dictadosPor(auth()->user()),
                ))
                ->with(['curso.unidadCurricular', 'curso.seccion'])
                ->withCount('asistencias')
                ->orderBy('hora_inicio'))
            ->emptyStateHeading('No hay clases programadas para hoy')
            ->paginated(false)
            ->columns([
                TextColumn::make('hora_inicio')
                    ->label('Horario')
                    ->state(fn (SesionClase $record): string => $record->horario()),
                TextColumn::make('curso.unidadCurricular.nombre')
                    ->label('Curso')
                    ->description(fn (SesionClase $record): string => $record->curso->seccion->codigo),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('asistencias_count')
                    ->label('Registrados'),
            ])
            ->recordActions([
                Action::make('qr')
                    ->label('QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('gray')
                    ->visible(fn (SesionClase $record): bool => auth()->user()->can('registrarAsistencia', $record))
                    ->url(fn (SesionClase $record): string => CursoResource::getUrl('qr', [
                        'record' => $record->curso,
                        'sesion' => $record,
                    ])),
                Action::make('asistencia')
                    ->label('Tomar asistencia')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->visible(fn (SesionClase $record): bool => auth()->user()->can('registrarAsistencia', $record))
                    ->url(fn (SesionClase $record): string => CursoResource::getUrl('asistencia', [
                        'record' => $record->curso,
                        'sesion' => $record,
                    ])),
            ]);
    }
}
