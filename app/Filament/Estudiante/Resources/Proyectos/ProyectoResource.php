<?php

namespace App\Filament\Estudiante\Resources\Proyectos;

use App\Actions\CalcularInasistencias;
use App\Actions\ResumenInasistencia;
use App\Filament\Estudiante\Resources\Concerns\SoloLectura;
use App\Filament\Estudiante\Resources\Proyectos\Pages\ListProyectos;
use App\Filament\Estudiante\Resources\Proyectos\Pages\ViewProyecto;
use App\Filament\Estudiante\Resources\Proyectos\RelationManagers\TutoriasRelationManager;
use App\Models\Estudiante;
use App\Models\IntegranteProyecto;
use App\Models\Proyecto;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use WeakMap;

/**
 * Proyectos de los que el estudiante es integrante, con su asistencia a
 * tutorías y su veredicto.
 */
class ProyectoResource extends Resource
{
    use SoloLectura;

    protected static ?string $model = Proyecto::class;

    protected static ?string $slug = 'proyectos';

    protected static ?string $navigationLabel = 'Mi proyecto';

    protected static ?string $modelLabel = 'proyecto';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    /**
     * Resúmenes por instancia del proyecto, para calcularlos una sola vez por petición.
     *
     * @var WeakMap<Proyecto, ResumenInasistencia>|null
     */
    private static ?WeakMap $resumenes = null;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->conIntegrante(static::estudiante())
            ->with(['curso.unidadCurricular', 'curso.seccion.periodo', 'curso.seccion.trayecto', 'curso.docentes', 'estudiantes' => fn ($estudiantes) => $estudiantes->orderBy('apellidos')->orderBy('nombres')]);
    }

    public static function estudiante(): Estudiante
    {
        return Filament::auth()->user();
    }

    public static function resumen(Proyecto $proyecto): ResumenInasistencia
    {
        static::$resumenes ??= new WeakMap;

        return static::$resumenes[$proyecto] ??= app(CalcularInasistencias::class)
            ->deProyecto($proyecto, static::estudiante())
            ->first();
    }

    public static function integrante(Proyecto $proyecto): ?IntegranteProyecto
    {
        return $proyecto->integrantes()->whereBelongsTo(static::estudiante())->first();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titulo')
                    ->label('Proyecto')
                    ->description(fn (Proyecto $record): string => $record->curso->unidadCurricular->nombre)
                    ->wrap(),
                TextColumn::make('curso.seccion.periodo.nombre')
                    ->label('Periodo'),
                TextColumn::make('porcentaje')
                    ->label('% inasistencia a tutorías')
                    ->state(fn (Proyecto $record): string => static::resumen($record)->porcentajeFormateado()),
                TextColumn::make('nivel')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Proyecto $record) => static::resumen($record)->nivel),
                TextColumn::make('veredicto')
                    ->badge()
                    ->placeholder('Pendiente')
                    ->state(fn (Proyecto $record) => static::integrante($record)?->veredicto),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make()->label('Ver'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('curso.unidadCurricular.nombre')->label('Unidad curricular'),
                        TextEntry::make('curso.seccion.codigo')->label('Sección'),
                        TextEntry::make('curso.seccion.periodo.nombre')->label('Periodo'),
                        TextEntry::make('comunidad')->label('Comunidad')->placeholder('—'),
                        TextEntry::make('curso.docentes.name')->label('Tutor(es) académico(s)')->badge()->placeholder('Sin asignar'),
                        TextEntry::make('tutor_comunitario')->label('Tutor comunitario')->placeholder('—'),
                        TextEntry::make('estudiantes.nombre_completo')->label('Integrantes')->listWithLineBreaks()->columnSpan(2),
                        TextEntry::make('descripcion')->label('Descripción')->placeholder('—'),
                    ]),
                Section::make('Mi situación')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('tutorias_registradas')
                            ->label('Tutorías registradas')
                            ->state(fn (Proyecto $record): int => static::resumen($record)->sesiones),
                        TextEntry::make('inasistencias')
                            ->state(fn (Proyecto $record): int => static::resumen($record)->ausentes),
                        TextEntry::make('porcentaje')
                            ->label('% inasistencia')
                            ->badge()
                            ->color(fn (Proyecto $record): string => static::resumen($record)->nivel->getColor())
                            ->state(fn (Proyecto $record): string => static::resumen($record)->porcentajeFormateado().' · '.static::resumen($record)->nivel->getLabel()),
                        TextEntry::make('veredicto')
                            ->badge()
                            ->placeholder('Pendiente')
                            ->state(fn (Proyecto $record) => static::integrante($record)?->veredicto),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TutoriasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProyectos::route('/'),
            'view' => ViewProyecto::route('/{record}'),
        ];
    }
}
