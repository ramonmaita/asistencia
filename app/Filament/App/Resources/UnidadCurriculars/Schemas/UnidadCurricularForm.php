<?php

namespace App\Filament\App\Resources\UnidadCurriculars\Schemas;

use App\Support\PnfActual;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class UnidadCurricularForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('trayecto_id')
                    ->relationship('trayecto', 'nombre', fn ($query) => $query->orderBy('numero'))
                    ->required(),
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('pnf_id', app(PnfActual::class)->id())),
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('horas_semanales')
                    ->label('Horas semanales')
                    ->integer()
                    ->minValue(1),
                Toggle::make('es_proyecto')
                    ->label('Es la unidad curricular Proyecto')
                    ->helperText('Habilita equipos, tutorías y veredicto para sus cursos.'),
            ]);
    }
}
