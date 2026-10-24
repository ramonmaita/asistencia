<?php

namespace App\Filament\App\Resources\Trayectos\Schemas;

use App\Enums\ModalidadProyecto;
use App\Support\PnfActual;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class TrayectoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Trayecto')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('numero')
                            ->label('Número')
                            ->options([0 => 'Inicial', 1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'])
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('pnf_id', app(PnfActual::class)->id())),
                        TextInput::make('nombre')
                            ->placeholder('Trayecto I')
                            ->required()
                            ->maxLength(255),
                    ]),
                Section::make('Unidad curricular Proyecto')
                    ->description('Cómo se conforman los proyectos de este trayecto y el control de tutorías.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('modalidad_proyecto')
                            ->label('Modalidad')
                            ->options(ModalidadProyecto::class)
                            ->default(ModalidadProyecto::Grupal)
                            ->required()
                            ->live(),
                        TextInput::make('umbral_inasistencia_tutorias')
                            ->label('Umbral de inasistencia a tutorías')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(25)
                            ->required(),
                        TextInput::make('min_integrantes')
                            ->label('Mínimo de integrantes')
                            ->integer()
                            ->minValue(1)
                            ->default(3)
                            ->required()
                            ->visible(fn (Get $get): bool => $get('modalidad_proyecto') === ModalidadProyecto::Grupal),
                        TextInput::make('max_integrantes')
                            ->label('Máximo de integrantes')
                            ->integer()
                            ->minValue(1)
                            ->gte('min_integrantes')
                            ->default(5)
                            ->required()
                            ->visible(fn (Get $get): bool => $get('modalidad_proyecto') === ModalidadProyecto::Grupal),
                    ]),
            ]);
    }
}
