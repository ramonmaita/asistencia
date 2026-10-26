<?php

namespace App\Filament\Admin\Resources\Estudiantes;

use App\Filament\Admin\Resources\Estudiantes\Pages\CreateEstudiante;
use App\Filament\Admin\Resources\Estudiantes\Pages\EditEstudiante;
use App\Filament\Admin\Resources\Estudiantes\Pages\ListEstudiantes;
use App\Filament\Admin\Resources\Estudiantes\Schemas\EstudianteForm;
use App\Filament\Admin\Resources\Estudiantes\Tables\EstudiantesTable;
use App\Models\Estudiante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Estudiantes de todos los PNF, para que el superadmin busque una cuenta,
 * la active o la cambie de PNF sin entrar a cada panel académico.
 */
class EstudianteResource extends Resource
{
    protected static ?string $model = Estudiante::class;

    protected static ?string $recordTitleAttribute = 'nombre_completo';

    protected static string|UnitEnum|null $navigationGroup = 'Usuarios';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function form(Schema $schema): Schema
    {
        return EstudianteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstudiantesTable::configure($table);
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
