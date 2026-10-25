<?php

namespace App\Filament\App\Resources\Justificaciones;

use App\Filament\App\Resources\Justificaciones\Pages\ListJustificaciones;
use App\Filament\App\Resources\Justificaciones\Tables\JustificacionesTable;
use App\Models\Justificacion;
use App\Models\SesionClase;
use App\Models\Tutoria;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Justificaciones solicitadas por los estudiantes, de clases y de tutorías.
 * El coordinador ve las de todo el PNF; el docente, las de sus cursos y de
 * los proyectos que tutora.
 */
class JustificacionResource extends Resource
{
    protected static ?string $model = Justificacion::class;

    protected static ?string $slug = 'justificaciones';

    protected static ?string $tenantRelationshipName = 'justificaciones';

    protected static ?string $modelLabel = 'justificación';

    protected static ?string $pluralModelLabel = 'justificaciones';

    protected static string|UnitEnum|null $navigationGroup = 'Asistencia';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    public static function getNavigationBadge(): ?string
    {
        $pendientes = static::getEloquentQuery()->pendientes()->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->coordinaPnfActual()) {
            $query->whereHas('asistencia', fn (Builder $asistencias) => $asistencias->whereHasMorph(
                'sesionable',
                [SesionClase::class, Tutoria::class],
                fn (Builder $sesiones, string $tipo) => $sesiones->whereHas(
                    $tipo === Tutoria::class ? 'proyecto.curso' : 'curso',
                    fn (Builder $cursos) => $cursos->dictadosPor($user),
                ),
            ));
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return JustificacionesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJustificaciones::route('/'),
        ];
    }
}
