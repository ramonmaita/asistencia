<?php

namespace App\Filament\App\Resources\Justificaciones\Tables;

use App\Enums\EstadoJustificacion;
use App\Filament\Estudiante\Resources\Justificaciones\JustificacionResource as JustificacionesDelEstudiante;
use App\Models\Justificacion;
use App\Models\SesionClase;
use App\Models\Tutoria;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JustificacionesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'estudiante',
                'revisor',
                'asistencia.sesionable' => fn (MorphTo $sesionable) => $sesionable->morphWith([
                    SesionClase::class => ['curso.unidadCurricular', 'curso.seccion'],
                    Tutoria::class => ['proyecto'],
                ]),
            ]))
            ->columns([
                TextColumn::make('estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->description(fn (Justificacion $record): string => $record->estudiante->cedula)
                    ->searchable(['cedula', 'nombres', 'apellidos']),
                TextColumn::make('sesion')
                    ->label('Sesión')
                    ->state(fn (Justificacion $record): string => $record->asistencia->sesionable->descripcion()),
                TextColumn::make('motivo')
                    ->limit(60)
                    ->tooltip(fn (Justificacion $record): string => $record->motivo)
                    ->wrap(),
                TextColumn::make('estado')
                    ->badge()
                    ->description(fn (Justificacion $record): ?string => $record->revisor ? "Por {$record->revisor->name}" : null),
                TextColumn::make('created_at')
                    ->label('Solicitada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->options(EstadoJustificacion::class)
                    ->default(EstadoJustificacion::Pendiente->value),
            ])
            ->recordActions([
                Action::make('soporte')
                    ->label('Soporte')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->color('gray')
                    ->action(fn (Justificacion $record): StreamedResponse => self::descargarSoporte($record)),
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Justificacion $record): bool => auth()->user()->can('revisar', $record))
                    ->modalHeading('Aprobar justificación')
                    ->modalDescription('La inasistencia quedará como justificada y no contará para el porcentaje.')
                    ->schema([
                        Textarea::make('observacion')
                            ->label('Observación (opcional)')
                            ->maxLength(1000),
                    ])
                    ->action(function (Justificacion $record, array $data): void {
                        $record->aprobar(auth()->user(), $data['observacion'] ?? null);
                        self::avisarAlEstudiante($record);

                        Notification::make()->title('Justificación aprobada')->success()->send();
                    }),
                Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (Justificacion $record): bool => auth()->user()->can('revisar', $record))
                    ->modalHeading('Rechazar justificación')
                    ->schema([
                        Textarea::make('observacion')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Justificacion $record, array $data): void {
                        $record->rechazar(auth()->user(), $data['observacion']);
                        self::avisarAlEstudiante($record);

                        Notification::make()->title('Justificación rechazada')->success()->send();
                    }),
            ]);
    }

    public static function descargarSoporte(Justificacion $justificacion): StreamedResponse
    {
        return Storage::disk(Justificacion::DISCO)->download($justificacion->soporte_path, $justificacion->soporte_nombre);
    }

    private static function avisarAlEstudiante(Justificacion $justificacion): void
    {
        $aprobada = $justificacion->estado === EstadoJustificacion::Aprobada;

        Notification::make()
            ->title($aprobada ? 'Tu justificación fue aprobada' : 'Tu justificación fue rechazada')
            ->body(collect([$justificacion->asistencia->sesionable->descripcion(), $justificacion->observacion_revision])->filter()->implode("\n"))
            ->icon($aprobada ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
            ->color($aprobada ? 'success' : 'danger')
            ->actions([
                Action::make('ver')
                    ->label('Ver mis justificaciones')
                    ->url(JustificacionesDelEstudiante::getUrl(panel: 'estudiante')),
            ])
            ->sendToDatabase($justificacion->estudiante);
    }
}
