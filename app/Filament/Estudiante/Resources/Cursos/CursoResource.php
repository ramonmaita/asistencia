<?php

namespace App\Filament\Estudiante\Resources\Cursos;

use App\Actions\CalcularInasistencias;
use App\Actions\ResumenInasistencia;
use App\Filament\Estudiante\Resources\Concerns\SoloLectura;
use App\Filament\Estudiante\Resources\Cursos\Pages\ListCursos;
use App\Filament\Estudiante\Resources\Cursos\Pages\ViewCurso;
use App\Filament\Estudiante\Resources\Cursos\RelationManagers\AsistenciasRelationManager;
use App\Models\Curso;
use App\Models\Estudiante;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use WeakMap;

/**
 * Cursos del estudiante con su porcentaje de inasistencia.
 */
class CursoResource extends Resource
{
    use SoloLectura;

    protected static ?string $model = Curso::class;

    protected static ?string $slug = 'cursos';

    protected static ?string $navigationLabel = 'Mis asistencias';

    protected static ?string $modelLabel = 'curso';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    /**
     * Resúmenes por instancia del curso, para calcularlos una sola vez por petición.
     *
     * @var WeakMap<Curso, ResumenInasistencia>|null
     */
    private static ?WeakMap $resumenes = null;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->cursadosPor(static::estudiante())
            ->with(['unidadCurricular', 'seccion.periodo', 'docentes', 'pnf']);
    }

    /**
     * Resumen de asistencia del estudiante autenticado en el curso (calculado una vez por petición).
     */
    public static function resumen(Curso $curso): ResumenInasistencia
    {
        static::$resumenes ??= new WeakMap;

        return static::$resumenes[$curso] ??= app(CalcularInasistencias::class)
            ->handle($curso, static::estudiante())
            ->first();
    }

    public static function estudiante(): Estudiante
    {
        return Filament::auth()->user();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unidadCurricular.nombre')
                    ->label('Unidad curricular')
                    ->description(fn (Curso $record): string => $record->unidadCurricular->codigo),
                TextColumn::make('seccion.codigo')
                    ->label('Sección'),
                TextColumn::make('seccion.periodo.nombre')
                    ->label('Periodo'),
                TextColumn::make('sesiones')
                    ->label('Clases registradas')
                    ->state(fn (Curso $record): int => static::resumen($record)->sesiones),
                TextColumn::make('inasistencias')
                    ->state(fn (Curso $record): int => static::resumen($record)->ausentes),
                TextColumn::make('porcentaje')
                    ->label('% inasistencia')
                    ->state(fn (Curso $record): string => static::resumen($record)->porcentajeFormateado()),
                TextColumn::make('nivel')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Curso $record) => static::resumen($record)->nivel),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('seccion_id'))
            ->filters([
                SelectFilter::make('periodo')
                    ->relationship('seccion.periodo', 'nombre'),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver asistencias'),
            ])
            ->recordUrl(fn (Curso $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('seccion.codigo')->label('Sección'),
                        TextEntry::make('seccion.periodo.nombre')->label('Periodo'),
                        TextEntry::make('docentes.name')->label('Docentes')->badge()->placeholder('Sin asignar'),
                        TextEntry::make('umbral')
                            ->label('Límite de inasistencia')
                            ->state(fn (Curso $record): string => number_format((float) $record->pnf->umbral_inasistencia_clases, 2, ',', '.').' %'),
                        TextEntry::make('sesiones')
                            ->label('Clases registradas')
                            ->state(fn (Curso $record): int => static::resumen($record)->sesiones),
                        TextEntry::make('asistencias')
                            ->state(fn (Curso $record): string => static::resumen($record)->asistencias().' (tardanzas: '.static::resumen($record)->tardanzas.')'),
                        TextEntry::make('inasistencias')
                            ->state(fn (Curso $record): string => static::resumen($record)->ausentes.' (justificadas aparte: '.static::resumen($record)->justificadas.')'),
                        TextEntry::make('porcentaje')
                            ->label('% inasistencia')
                            ->badge()
                            ->color(fn (Curso $record): string => static::resumen($record)->nivel->getColor())
                            ->state(fn (Curso $record): string => static::resumen($record)->porcentajeFormateado().' · '.static::resumen($record)->nivel->getLabel()),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AsistenciasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCursos::route('/'),
            'view' => ViewCurso::route('/{record}'),
        ];
    }
}
