<?php

namespace App\Filament\App\Resources\Estudiantes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class EstudianteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components(static::campos());
    }

    /**
     * Campos de la cuenta del estudiante; el panel de administración les
     * antepone el PNF.
     *
     * @return array<int, Component>
     */
    public static function campos(): array
    {
        return [
            TextInput::make('cedula')
                ->label('Cédula')
                ->required()
                ->regex('/^\d{6,10}$/')
                ->validationMessages([
                    'regex' => 'La cédula debe tener solo números (6 a 10 dígitos).',
                    'unique' => 'Ya existe un estudiante con esta cédula (puede estar en otro PNF).',
                ])
                ->unique(ignoreRecord: true)
                ->helperText(fn (string $operation): ?string => $operation === 'create'
                    ? 'La contraseña inicial será la cédula; se le pedirá cambiarla al entrar.'
                    : null),
            TextInput::make('nombres')
                ->required()
                ->maxLength(255),
            TextInput::make('apellidos')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Correo')
                ->email(),
            TextInput::make('telefono')
                ->label('Teléfono')
                ->tel(),
            Toggle::make('activo')
                ->default(true),
        ];
    }
}
