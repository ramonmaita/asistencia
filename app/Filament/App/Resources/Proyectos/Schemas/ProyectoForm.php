<?php

namespace App\Filament\App\Resources\Proyectos\Schemas;

use App\Models\Curso;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProyectoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Proyecto')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('curso_id')
                            ->label('Curso de la UC Proyecto')
                            ->helperText('Sus docentes son los tutores académicos del proyecto.')
                            ->relationship('curso', 'id', fn (Builder $query) => $query
                                ->deProyecto()
                                ->when(! auth()->user()->coordinaPnfActual(), fn (Builder $cursos) => $cursos->dictadosPor(auth()->user()))
                                ->with(['unidadCurricular', 'seccion.periodo'])
                                ->latest('id'))
                            ->getOptionLabelFromRecordUsing(fn (Curso $record): string => "{$record->unidadCurricular->nombre} · {$record->seccion->codigo} · {$record->seccion->periodo->nombre}")
                            ->preload()
                            ->required()
                            ->disabledOn('edit')
                            ->columnSpanFull(),
                        TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(3000)
                            ->columnSpanFull(),
                        TextInput::make('comunidad')
                            ->label('Comunidad u organización')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('tutor_comunitario')
                            ->label('Tutor comunitario')
                            ->maxLength(255),
                        TextInput::make('tutor_comunitario_contacto')
                            ->label('Contacto del tutor comunitario')
                            ->helperText('Teléfono o correo.')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
