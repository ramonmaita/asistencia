<?php

namespace App\Filament\App\Resources\Cursos\RelationManagers;

use App\Actions\CalcularInasistencias;
use App\Actions\ExportarResumenAsistencia;
use App\Actions\ImportarEstudiantes;
use App\Actions\ResumenInasistencia;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Filament\App\Support\ImportacionDeEstudiantes;
use App\Models\Estudiante;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Estudiantes inscritos en el curso con su resumen de asistencia
 * (asistencias, inasistencias, porcentaje y estado) y exportación a Excel
 * y PDF. El coordinador y los docentes del curso inscriben y retiran
 * estudiantes desde aquí, o importan la lista del sistema académico.
 */
class ResumenAsistenciaRelationManager extends RelationManager
{
    protected static string $relationship = 'estudiantes';

    protected static ?string $title = 'Estudiantes y asistencia';

    /**
     * Filament adivinaría "cursos" igual, pero se deja explícito.
     */
    protected static ?string $inverseRelationship = 'cursos';

    protected static ?string $modelLabel = 'estudiante';

    /** @var Collection<int, ResumenInasistencia>|null */
    private ?Collection $resumenes = null;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return CursoResource::canView($ownerRecord);
    }

    /**
     * El curso se ve en solo lectura, pero la inscripción se gestiona desde
     * aquí (cada acción comprueba la política gestionarInscripcion).
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    private function puedeInscribir(): bool
    {
        return (bool) auth()->user()?->can('gestionarInscripcion', $this->getOwnerRecord());
    }

    private function resumen(Estudiante $estudiante): ResumenInasistencia
    {
        $this->resumenes ??= app(CalcularInasistencias::class)->handle($this->getOwnerRecord());

        return $this->resumenes->get($estudiante->getKey())
            ?? app(CalcularInasistencias::class)->handle($this->getOwnerRecord(), $estudiante)->first();
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
                    Action::make('importarLista')
                        ->label('Importar lista (Excel o CSV)')
                        ->icon(Heroicon::OutlinedArrowUpTray)
                        ->modalHeading('Importar la lista del curso')
                        ->modalDescription(ImportacionDeEstudiantes::DESCRIPCION.' Quedan inscritos en este curso y en su sección.')
                        ->modalSubmitActionLabel('Importar')
                        ->schema([
                            ImportacionDeEstudiantes::campoArchivo(),
                        ])
                        ->action(function (array $data, ImportarEstudiantes $importar): void {
                            $curso = $this->getOwnerRecord();
                            $disco = Storage::disk('local');

                            try {
                                $resultado = $importar->handle($disco->path($data['archivo']), $curso->pnf, curso: $curso);
                            } finally {
                                $disco->delete($data['archivo']);
                            }

                            $this->resumenes = null;
                            ImportacionDeEstudiantes::notificar($resultado, conInscritos: true);
                        }),
                    ImportacionDeEstudiantes::plantillaAction(),
                ])
                    ->label('Importar')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->button()
                    ->color('gray')
                    ->visible(fn (): bool => $this->puedeInscribir()),
                AttachAction::make()
                    ->label('Inscribir estudiantes')
                    ->modalHeading('Inscribir estudiantes en el curso')
                    ->modalDescription('Puedes inscribir estudiantes de otras secciones del PNF (por ejemplo, quienes repiten la unidad curricular).')
                    ->modalSubmitActionLabel('Inscribir')
                    ->attachAnother(false)
                    ->multiple()
                    ->recordTitle(fn (Estudiante $record): string => "{$record->cedula} · {$record->nombre_completo}")
                    ->recordSelectSearchColumns(['cedula', 'nombres', 'apellidos'])
                    ->successNotificationTitle('Estudiantes inscritos')
                    ->visible(fn (): bool => $this->puedeInscribir()),
                Action::make('inscribirSeccion')
                    ->label('Inscribir a toda la sección')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->color('gray')
                    ->visible(fn (): bool => $this->puedeInscribir())
                    ->requiresConfirmation()
                    ->modalDescription(fn (): string => "Se inscriben en el curso todos los estudiantes de la sección {$this->getOwnerRecord()->seccion->codigo} que aún no lo estén, incluidos quienes solo repiten otra unidad en ella. Revisa la lista después.")
                    ->action(function (): void {
                        $curso = $this->getOwnerRecord();
                        $nuevos = $curso->estudiantes()->syncWithoutDetaching($curso->seccion->estudiantes()->pluck('estudiantes.id'))['attached'];

                        Notification::make()->title('Se inscribieron '.count($nuevos).' estudiantes')->success()->send();
                    }),
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
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Retirar')
                    ->modalDescription('El estudiante deja de estar inscrito en esta unidad curricular. Su historial de asistencia se conserva.')
                    ->visible(fn (): bool => $this->puedeInscribir()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Retirar seleccionados')
                        ->visible(fn (): bool => $this->puedeInscribir()),
                ]),
            ]);
    }
}
