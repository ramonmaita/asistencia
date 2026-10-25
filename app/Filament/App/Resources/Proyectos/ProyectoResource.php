<?php

namespace App\Filament\App\Resources\Proyectos;

use App\Filament\App\Resources\Proyectos\Pages\CreateProyecto;
use App\Filament\App\Resources\Proyectos\Pages\EditProyecto;
use App\Filament\App\Resources\Proyectos\Pages\ListProyectos;
use App\Filament\App\Resources\Proyectos\Pages\QrDinamicoTutoria;
use App\Filament\App\Resources\Proyectos\Pages\RegistrarAsistenciaTutoria;
use App\Filament\App\Resources\Proyectos\Pages\ViewProyecto;
use App\Filament\App\Resources\Proyectos\RelationManagers\IntegrantesRelationManager;
use App\Filament\App\Resources\Proyectos\RelationManagers\TutoriasRelationManager;
use App\Filament\App\Resources\Proyectos\Schemas\ProyectoForm;
use App\Filament\App\Resources\Proyectos\Tables\ProyectosTable;
use App\Models\Proyecto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Proyectos de la UC Proyecto. El coordinador ve todos los del PNF; el
 * docente, los de los cursos que dicta (es su tutor académico).
 */
class ProyectoResource extends Resource
{
    protected static ?string $model = Proyecto::class;

    protected static ?string $recordTitleAttribute = 'titulo';

    protected static string|UnitEnum|null $navigationGroup = 'Académico';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->coordinaPnfActual() ? 'Proyectos' : 'Mis proyectos';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->coordinaPnfActual()) {
            $query->tutoradosPor($user);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return ProyectoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProyectosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            IntegrantesRelationManager::class,
            TutoriasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProyectos::route('/'),
            'create' => CreateProyecto::route('/create'),
            'view' => ViewProyecto::route('/{record}'),
            'edit' => EditProyecto::route('/{record}/edit'),
            'tutoria' => RegistrarAsistenciaTutoria::route('/{record}/tutorias/{tutoria}/asistencia'),
            'qrTutoria' => QrDinamicoTutoria::route('/{record}/tutorias/{tutoria}/qr'),
        ];
    }
}
