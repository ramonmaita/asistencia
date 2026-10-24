<?php

namespace App\Filament\App\Resources\Cursos\RelationManagers;

use App\Actions\CalcularInasistencias;
use App\Actions\ExportarResumenAsistencia;
use App\Actions\ResumenInasistencia;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Models\Estudiante;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Resumen por estudiante: asistencias, inasistencias, porcentaje y estado,
 * con exportación a Excel y PDF.
 */
class ResumenAsistenciaRelationManager extends RelationManager
{
    protected static string $relationship = 'estudiantes';

    protected static ?string $title = 'Resumen de asistencia';

    protected static ?string $modelLabel = 'estudiante';

    /** @var Collection<int, ResumenInasistencia>|null */
    private ?Collection $resumenes = null;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return CursoResource::canView($ownerRecord);
    }

    private function resumen(Estudiante $estudiante): ResumenInasistencia
    {
        $this->resumenes ??= app(CalcularInasistencias::class)->handle($this->getOwnerRecord());

        return $this->resumenes->get($estudiante->getKey());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre_completo')
            ->columns([
                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombres')
                    ->searchable(),
                TextColumn::make('sesiones')
                    ->label('Clases')
                    ->state(fn (Estudiante $record): int => $this->resumen($record)->sesiones),
                TextColumn::make('asistencias')
                    ->state(fn (Estudiante $record): int => $this->resumen($record)->asistencias()),
                TextColumn::make('inasistencias')
                    ->state(fn (Estudiante $record): int => $this->resumen($record)->ausentes),
                TextColumn::make('justificadas')
                    ->state(fn (Estudiante $record): int => $this->resumen($record)->justificadas),
                TextColumn::make('porcentaje')
                    ->label('% inasistencia')
                    ->state(fn (Estudiante $record): string => $this->resumen($record)->porcentajeFormateado()),
                TextColumn::make('nivel')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Estudiante $record) => $this->resumen($record)->nivel),
            ])
            ->defaultSort('apellidos')
            ->headerActions([
                ActionGroup::make([
                    Action::make('exportarExcel')
                        ->label('Excel')
                        ->icon(Heroicon::OutlinedTableCells)
                        ->action(fn (ExportarResumenAsistencia $exportar): StreamedResponse => $exportar->excel($this->getOwnerRecord())),
                    Action::make('exportarPdf')
                        ->label('PDF')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->action(fn (ExportarResumenAsistencia $exportar): StreamedResponse => $exportar->pdf($this->getOwnerRecord())),
                ])
                    ->label('Exportar')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->button()
                    ->color('gray'),
            ]);
    }
}
