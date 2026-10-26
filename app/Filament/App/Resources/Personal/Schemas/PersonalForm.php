<?php

namespace App\Filament\App\Resources\Personal\Schemas;

use App\Enums\RolPnf;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PersonalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(255),
                TextInput::make('cedula')
                    ->label('Cédula')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Ya existe una cuenta con esta cédula: agrégala con "Agregar personal existente".',
                    ]),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Ya existe una cuenta con este correo: agrégala con "Agregar personal existente".',
                    ]),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Déjala vacía para no cambiarla.' : null),
                static::campoRol()
                    ->visibleOn('create'),
            ]);
    }

    /**
     * Solo el coordinador (o el superadmin) nombra a otro coordinador.
     */
    public static function campoRol(): Select
    {
        return Select::make('rol')
            ->label('Rol en el PNF')
            ->options(fn (): array => collect(RolPnf::cases())
                ->reject(fn (RolPnf $rol): bool => $rol === RolPnf::Coordinador && ! auth()->user()->coordinaPnfActual())
                ->mapWithKeys(fn (RolPnf $rol): array => [$rol->value => $rol->getLabel()])
                ->all())
            ->default(RolPnf::Docente->value)
            ->required();
    }
}
