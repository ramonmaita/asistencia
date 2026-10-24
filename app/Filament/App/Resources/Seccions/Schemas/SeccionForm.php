<?php

namespace App\Filament\App\Resources\Seccions\Schemas;

use App\Enums\Turno;
use App\Models\Periodo;
use App\Support\PnfActual;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SeccionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('periodo_id')
                    ->relationship('periodo', 'nombre', fn ($query) => $query->orderByDesc('fecha_inicio'))
                    ->default(fn (): ?int => Periodo::activo()->value('id'))
                    ->required(),
                Select::make('trayecto_id')
                    ->relationship('trayecto', 'nombre', fn ($query) => $query->orderBy('numero'))
                    ->required(),
                TextInput::make('codigo')
                    ->label('Código')
                    ->placeholder('Sección 01')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                        ->where('pnf_id', app(PnfActual::class)->id())
                        ->where('periodo_id', $get('periodo_id'))),
                Select::make('turno')
                    ->options(Turno::class),
                Select::make('sede_id')
                    ->relationship('sede', 'nombre', fn ($query) => $query->where('activo', true)),
            ]);
    }
}
