<?php

namespace App\Filament\Estudiante\Resources\Justificaciones;

use App\Filament\App\Resources\Justificaciones\Tables\JustificacionesTable;
use App\Filament\Estudiante\Resources\Concerns\SoloLectura;
use App\Filament\Estudiante\Resources\Justificaciones\Pages\ListJustificaciones;
use App\Models\Justificacion;
use App\Models\SesionClase;
use App\Models\Tutoria;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Justificaciones que ha solicitado el estudiante y su resultado.
 */
class JustificacionResource extends Resource
{
    use SoloLectura;

    protected static ?string $model = Justificacion::class;

    protected static ?string $slug = 'justificaciones';

    protected static ?string $modelLabel = 'justificación';

    protected static ?string $pluralModelLabel = 'justificaciones';

    protected static ?string $navigationLabel = 'Mis justificaciones';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereBelongsTo(Filament::auth()->user())
            ->with([
                'asistencia.sesionable' => fn (MorphTo $sesionable) => $sesionable->morphWith([
                    SesionClase::class => ['curso.unidadCurricular'],
                    Tutoria::class => ['proyecto'],
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sesion')
                    ->label('Clase')
                    ->state(fn (Justificacion $record): string => $record->asistencia->sesionable->descripcion()),
                TextColumn::make('motivo')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('observacion_revision')
                    ->label('Observación del docente')
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Solicitada')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('soporte')
                    ->label('Soporte')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->color('gray')
                    ->action(fn (Justificacion $record): StreamedResponse => JustificacionesTable::descargarSoporte($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJustificaciones::route('/'),
        ];
    }
}
