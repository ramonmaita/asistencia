<?php

namespace App\Filament\Estudiante\Resources\Cursos\RelationManagers;

use App\Actions\SolicitarJustificacion;
use App\Enums\EstadoAsistencia;
use App\Filament\Estudiante\Resources\Cursos\CursoResource;
use App\Models\Asistencia;
use App\Models\Justificacion;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Histórico de asistencias del estudiante en el curso; desde aquí solicita
 * la justificación de una inasistencia.
 */
class AsistenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'asistencias';

    protected static ?string $title = 'Mis asistencias';

    protected static ?string $modelLabel = 'asistencia';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('asistencias.estudiante_id', CursoResource::estudiante()->getKey())
                ->with(['sesionable', 'justificaciones' => fn ($justificaciones) => $justificaciones->latest()]))
            ->columns([
                TextColumn::make('sesionable.fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->description(fn (Asistencia $record): string => $record->sesionable->horario()),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('justificacion')
                    ->label('Justificación')
                    ->badge()
                    ->state(fn (Asistencia $record) => $record->justificaciones->first()?->estado)
                    ->placeholder('—'),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('sesiones_clase.fecha')->orderByDesc('sesiones_clase.hora_inicio'))
            ->filters([
                SelectFilter::make('estado')
                    ->options(EstadoAsistencia::class),
            ])
            ->recordActions([
                Action::make('justificar')
                    ->label('Justificar')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->visible(fn (Asistencia $record): bool => $record->puedeJustificarse())
                    ->modalHeading('Solicitar justificación')
                    ->modalDescription(fn (Asistencia $record): string => $record->sesionable->descripcion())
                    ->modalSubmitActionLabel('Enviar solicitud')
                    ->schema([
                        Textarea::make('motivo')
                            ->required()
                            ->maxLength(1000),
                        FileUpload::make('soporte')
                            ->label('Soporte (PDF o imagen)')
                            ->helperText('Constancia médica, citación u otro documento. Máximo 5 MB.')
                            ->disk(Justificacion::DISCO)
                            ->directory(Justificacion::DIRECTORIO)
                            ->visibility('private')
                            ->acceptedFileTypes(Justificacion::TIPOS_PERMITIDOS)
                            ->maxSize(Justificacion::TAMANO_MAXIMO)
                            ->storeFileNamesIn('soporte_nombre')
                            ->required(),
                    ])
                    ->action(function (Asistencia $record, array $data, SolicitarJustificacion $solicitar): void {
                        try {
                            $solicitar->handle(CursoResource::estudiante(), $record, $data['motivo'], $data['soporte'], $data['soporte_nombre']);
                        } catch (Throwable $error) {
                            Storage::disk(Justificacion::DISCO)->delete($data['soporte']);

                            throw $error;
                        }

                        Notification::make()
                            ->title('Solicitud enviada')
                            ->body('Tu docente la revisará pronto.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
