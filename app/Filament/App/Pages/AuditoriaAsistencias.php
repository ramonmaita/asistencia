<?php

namespace App\Filament\App\Pages;

use App\Actions\ExportarHojaDeCalculo;
use App\Enums\EstadoAsistencia;
use App\Enums\MetodoAsistencia;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Periodo;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\Tutoria;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Auditoría de asistencias del PNF para el coordinador y el administrativo: clases y tutorías,
 * con filtros combinados por tipo, fecha, periodo, sección y curso.
 *
 * La consulta une las dos tablas de sesiones; fecha, hora y curso salen de
 * la que corresponda a cada registro (en las tutorías, el curso del proyecto).
 */
class AuditoriaAsistencias extends Page implements HasTable
{
    use InteractsWithTable;

    private const FECHA = 'coalesce(sesiones_clase.fecha, tutorias.fecha)';

    private const HORA = 'coalesce(sesiones_clase.hora_inicio, tutorias.hora_inicio)';

    private const CURSO = 'coalesce(sesiones_clase.curso_id, proyectos.curso_id)';

    protected static ?string $title = 'Auditoría de asistencias';

    protected static ?string $slug = 'auditoria-asistencias';

    protected static string|UnitEnum|null $navigationGroup = 'Asistencia';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->administraPnfActual();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Asistencia::query()
                ->leftJoin('sesiones_clase', fn (JoinClause $join) => $join
                    ->on('sesiones_clase.id', '=', 'asistencias.sesionable_id')
                    ->where('asistencias.sesionable_type', (new SesionClase)->getMorphClass()))
                ->leftJoin('tutorias', fn (JoinClause $join) => $join
                    ->on('tutorias.id', '=', 'asistencias.sesionable_id')
                    ->where('asistencias.sesionable_type', (new Tutoria)->getMorphClass()))
                ->leftJoin('proyectos', 'proyectos.id', '=', 'tutorias.proyecto_id')
                ->select('asistencias.*', DB::raw(self::FECHA.' as fecha_sesion'))
                ->with([
                    'estudiante',
                    'registrador',
                    'sesionable' => fn (MorphTo $sesionable) => $sesionable->morphWith([
                        SesionClase::class => ['curso.unidadCurricular', 'curso.seccion'],
                        Tutoria::class => ['proyecto.curso.unidadCurricular', 'proyecto.curso.seccion'],
                    ]),
                ]))
            ->columns([
                TextColumn::make('fecha_sesion')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->description(fn (Asistencia $record): string => $record->sesionable->horario())
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw(self::FECHA.' '.($direction === 'asc' ? 'asc' : 'desc'))),
                TextColumn::make('sesionable_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (Asistencia $record): string => $record->sesionable instanceof Tutoria ? 'Tutoría' : 'Clase')
                    ->color(fn (Asistencia $record): string => $record->sesionable instanceof Tutoria ? 'info' : 'gray'),
                TextColumn::make('curso')
                    ->label('Curso')
                    ->state(fn (Asistencia $record): string => $this->cursoDe($record)->unidadCurricular->nombre)
                    ->description(fn (Asistencia $record): string => $record->sesionable instanceof Tutoria
                        ? "{$this->cursoDe($record)->seccion->codigo} · {$record->sesionable->proyecto->titulo}"
                        : $this->cursoDe($record)->seccion->codigo)
                    ->wrap(),
                TextColumn::make('estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->description(fn (Asistencia $record): string => $record->estudiante->cedula)
                    ->searchable(['cedula', 'nombres', 'apellidos']),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('metodo')
                    ->label('Método'),
                TextColumn::make('registrador.name')
                    ->label('Registrado por')
                    ->placeholder('—'),
                TextColumn::make('registrado_at')
                    ->label('Registrado el')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByRaw(self::FECHA.' desc')->orderByRaw(self::HORA.' desc')->orderBy('asistencias.id'))
            ->filters([
                SelectFilter::make('tipo')
                    ->options([
                        (new SesionClase)->getMorphClass() => 'Clases',
                        (new Tutoria)->getMorphClass() => 'Tutorías',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $query, string $tipo) => $query->where('asistencias.sesionable_type', $tipo))),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn (Builder $query, string $desde) => $query->whereRaw('date('.self::FECHA.') >= ?', [$desde]))
                        ->when($data['hasta'] ?? null, fn (Builder $query, string $hasta) => $query->whereRaw('date('.self::FECHA.') <= ?', [$hasta])))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        filled($data['desde'] ?? null) ? Indicator::make('Desde '.date('d/m/Y', strtotime($data['desde']))) : null,
                        filled($data['hasta'] ?? null) ? Indicator::make('Hasta '.date('d/m/Y', strtotime($data['hasta']))) : null,
                    ]))),
                SelectFilter::make('periodo')
                    ->options(fn (): array => Periodo::query()->latest('fecha_inicio')->pluck('nombre', 'id')->all())
                    ->default(fn (): ?int => Periodo::activo()->value('id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $query, int|string $periodo) => $query
                        ->whereIn(DB::raw(self::CURSO), Curso::query()->whereHas('seccion', fn (Builder $secciones) => $secciones->where('periodo_id', $periodo))->select('id')))),
                SelectFilter::make('seccion')
                    ->label('Sección')
                    ->options(fn (): array => Seccion::query()->with('periodo')->orderBy('codigo')->get()
                        ->mapWithKeys(fn (Seccion $seccion): array => [$seccion->id => "{$seccion->codigo} · {$seccion->periodo->nombre}"])
                        ->all())
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $query, int|string $seccion) => $query
                        ->whereIn(DB::raw(self::CURSO), Curso::query()->where('seccion_id', $seccion)->select('id')))),
                SelectFilter::make('curso')
                    ->options(fn (): array => Curso::query()->with(['unidadCurricular', 'seccion'])->get()
                        ->mapWithKeys(fn (Curso $curso): array => [$curso->id => "{$curso->unidadCurricular->nombre} · {$curso->seccion->codigo}"])
                        ->sort()
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $query, int|string $curso) => $query
                        ->where(DB::raw(self::CURSO), $curso))),
                SelectFilter::make('estado')
                    ->options(EstadoAsistencia::class)
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $query, string $estado) => $query->where('asistencias.estado', $estado))),
                SelectFilter::make('metodo')
                    ->label('Método')
                    ->options(MetodoAsistencia::class)
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $query, string $metodo) => $query->where('asistencias.metodo', $metodo))),
            ])
            ->filtersFormColumns(3)
            ->headerActions([
                Action::make('exportarExcel')
                    ->label('Exportar a Excel')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (ExportarHojaDeCalculo $exportar): StreamedResponse => $exportar->handle(
                        ['Fecha', 'Hora', 'Tipo', 'Unidad curricular', 'Sección', 'Proyecto / tema', 'Cédula', 'Estudiante', 'Estado', 'Método', 'Registrado por', 'Registrado el'],
                        $this->getFilteredSortedTableQuery()->lazy()->map(fn (Asistencia $asistencia): array => [
                            $asistencia->sesionable->fecha->format('d/m/Y'),
                            $asistencia->sesionable->horario(),
                            $asistencia->sesionable instanceof Tutoria ? 'Tutoría' : 'Clase',
                            $this->cursoDe($asistencia)->unidadCurricular->nombre,
                            $this->cursoDe($asistencia)->seccion->codigo,
                            $asistencia->sesionable instanceof Tutoria ? "{$asistencia->sesionable->proyecto->titulo} · {$asistencia->sesionable->tema}" : null,
                            $asistencia->estudiante->cedula,
                            $asistencia->estudiante->nombre_completo,
                            $asistencia->estado->getLabel(),
                            $asistencia->metodo->getLabel(),
                            $asistencia->registrador?->name,
                            $asistencia->registrado_at?->format('d/m/Y H:i'),
                        ]),
                        'auditoria-asistencias.xlsx',
                    )),
            ]);
    }

    /**
     * Curso de la sesión: el de la clase o el del proyecto de la tutoría.
     */
    private function cursoDe(Asistencia $asistencia): Curso
    {
        $sesion = $asistencia->sesionable;

        return $sesion instanceof Tutoria ? $sesion->proyecto->curso : $sesion->curso;
    }
}
