<?php

namespace App\Filament\App\Resources\Cursos\Schemas;

use App\Enums\DiaSemana;
use App\Models\Seccion;
use App\Models\UnidadCurricular;
use App\Support\PnfActual;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class CursoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Curso')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('seccion_id')
                            ->label('Sección')
                            ->relationship('seccion', 'codigo', fn (Builder $query) => $query->with(['trayecto', 'periodo'])->latest('periodo_id'))
                            ->getOptionLabelFromRecordUsing(fn (Seccion $record): string => "{$record->codigo} · {$record->trayecto->nombre} · {$record->periodo->nombre}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('unidad_curricular_id', null)),
                        Select::make('unidad_curricular_id')
                            ->label('Unidad curricular')
                            ->helperText('Solo las del trayecto de la sección.')
                            ->relationship('unidadCurricular', 'nombre', fn (Builder $query, Get $get) => $query
                                ->where('trayecto_id', Seccion::find($get('seccion_id'))?->trayecto_id))
                            ->getOptionLabelFromRecordUsing(fn (UnidadCurricular $record): string => "{$record->codigo} · {$record->nombre}")
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('seccion_id', $get('seccion_id')))
                            ->validationMessages(['unique' => 'Esta unidad curricular ya tiene un curso en la sección.']),
                        Select::make('docentes')
                            ->relationship('docentes', 'name', fn (Builder $query) => $query
                                ->whereHas('pnfs', fn (Builder $pnfs) => $pnfs->whereKey(app(PnfActual::class)->id())))
                            ->multiple()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
                Section::make('Horario semanal')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('horarios')
                            ->hiddenLabel()
                            ->relationship()
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Agregar bloque')
                            ->schema([
                                Select::make('dia_semana')
                                    ->label('Día')
                                    ->options(DiaSemana::class)
                                    ->required(),
                                TimePicker::make('hora_inicio')
                                    ->label('Desde')
                                    ->seconds(false)
                                    ->required(),
                                TimePicker::make('hora_fin')
                                    ->label('Hasta')
                                    ->seconds(false)
                                    ->after('hora_inicio')
                                    ->required(),
                                TextInput::make('aula')
                                    ->maxLength(50),
                            ]),
                    ]),
            ]);
    }
}
