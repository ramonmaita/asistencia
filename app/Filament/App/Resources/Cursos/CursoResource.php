<?php

namespace App\Filament\App\Resources\Cursos;

use App\Filament\App\Resources\Cursos\Pages\CreateCurso;
use App\Filament\App\Resources\Cursos\Pages\EditCurso;
use App\Filament\App\Resources\Cursos\Pages\ListCursos;
use App\Filament\App\Resources\Cursos\Pages\ViewCurso;
use App\Filament\App\Resources\Cursos\Schemas\CursoForm;
use App\Filament\App\Resources\Cursos\Tables\CursosTable;
use App\Models\Curso;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Cursos (UC dictada en una sección). El coordinador ve todos los del PNF;
 * el docente solo los que tiene asignados.
 */
class CursoResource extends Resource
{
    protected static ?string $model = Curso::class;

    protected static string|UnitEnum|null $navigationGroup = 'Académico';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->coordinaPnfActual() ? 'Cursos' : 'Mis cursos';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->coordinaPnfActual()) {
            $query->dictadosPor($user);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return CursoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CursosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCursos::route('/'),
            'create' => CreateCurso::route('/create'),
            'view' => ViewCurso::route('/{record}'),
            'edit' => EditCurso::route('/{record}/edit'),
        ];
    }
}
