<?php

namespace App\Filament\Estudiante\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/**
 * Los estudiantes inician sesión con su cédula en lugar del correo.
 */
class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Cédula')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'cedula' => $data['email'],
            'password' => $data['password'],
        ];
    }
}
