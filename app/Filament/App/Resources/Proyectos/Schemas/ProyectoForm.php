<?php

namespace App\Filament\App\Resources\Proyectos\Schemas;

use App\Models\Curso;
use App\Models\User;
use App\Support\PnfActual;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                            ->live()
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
                            ->label('Comunidad o institución')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Tutores')
                    ->description('El tutor académico es el docente del curso de la UC Proyecto.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('tutor_tecnico_id')
                            ->label('Tutor técnico')
                            ->helperText('Docente del PNF distinto al de la UC Proyecto de la sección.')
                            ->options(fn (Get $get): array => User::query()
                                ->whereHas('pnfs', fn (Builder $pnfs) => $pnfs->whereKey(app(PnfActual::class)->id()))
                                ->whereDoesntHave('cursos', fn (Builder $cursos) => $cursos->whereKey($get('curso_id')))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                $esDelPnf = User::whereKey($value)->whereHas('pnfs', fn (Builder $pnfs) => $pnfs->whereKey(app(PnfActual::class)->id()))->exists();
                                $esAcademico = Curso::whereKey($get('curso_id'))->whereHas('docentes', fn (Builder $docentes) => $docentes->whereKey($value))->exists();

                                if (! $esDelPnf || $esAcademico) {
                                    $fail('El tutor técnico debe ser un docente del PNF distinto al de la UC Proyecto.');
                                }
                            })
                            ->searchable()
                            ->columnSpanFull(),
                        TextInput::make('tutor_institucional')
                            ->label('Tutor institucional')
                            ->helperText('Persona de la comunidad o institución, externa a la universidad.')
                            ->maxLength(255),
                        TextInput::make('tutor_institucional_contacto')
                            ->label('Contacto del tutor institucional')
                            ->helperText('Teléfono o correo.')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
