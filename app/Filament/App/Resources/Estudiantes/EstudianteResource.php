<?php

namespace App\Filament\App\Resources\Estudiantes;

use App\Filament\App\Resources\Estudiantes\Pages\CreateEstudiante;
use App\Filament\App\Resources\Estudiantes\Pages\EditEstudiante;
use App\Filament\App\Resources\Estudiantes\Pages\ListEstudiantes;
use App\Filament\App\Resources\Estudiantes\RelationManagers\CursosRelationManager;
use App\Filament\App\Resources\Estudiantes\Schemas\EstudianteForm;
use App\Filament\App\Resources\Estudiantes\Tables\EstudiantesTable;
use App\Models\Estudiante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EstudianteResource extends Resource
{
    protected static ?string $model = Estudiante::class;

    protected static ?string $recordTitleAttribute = 'nombre_completo';

    protected static string|UnitEnum|null $navigationGroup = 'Académico';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function form(Schema $schema): Schema
    {
        return EstudianteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstudiantesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CursosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstudiantes::route('/'),
            'create' => CreateEstudiante::route('/create'),
            'edit' => EditEstudiante::route('/{record}/edit'),
        ];
    }
}
