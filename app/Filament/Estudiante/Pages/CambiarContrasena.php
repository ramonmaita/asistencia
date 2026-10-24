<?php

namespace App\Filament\Estudiante\Pages;

use App\Models\Estudiante;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Cambio de contraseña del estudiante. Es obligatorio mientras conserve
 * la contraseña inicial (ver ExigirCambioDeContrasena).
 */
class CambiarContrasena extends Page
{
    protected static ?string $title = 'Cambiar contraseña';

    protected static ?string $slug = 'cambiar-contrasena';

    protected static ?int $navigationSort = 99;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description(fn (): ?string => $this->estudiante()->debe_cambiar_password
                        ? 'Por seguridad, debes cambiar la contraseña inicial antes de continuar.'
                        : null)
                    ->schema([
                        TextInput::make('contrasena_actual')
                            ->label('Contraseña actual')
                            ->password()
                            ->revealable()
                            ->required()
                            ->currentPassword(guard: 'estudiante'),
                        TextInput::make('contrasena')
                            ->label('Nueva contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::min(8)->letters()->numbers())
                            ->different('contrasena_actual')
                            ->notIn(fn (): array => [$this->estudiante()->cedula])
                            ->validationMessages(['not_in' => 'La nueva contraseña no puede ser tu cédula.'])
                            ->confirmed(),
                        TextInput::make('contrasena_confirmation')
                            ->label('Confirmar nueva contraseña')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('guardar')
                    ->footer([
                        Actions::make([
                            Action::make('guardar')
                                ->label('Guardar contraseña')
                                ->submit('guardar'),
                        ]),
                    ]),
            ]);
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();
        $estudiante = $this->estudiante();

        $estudiante->forceFill([
            'password' => Hash::make($datos['contrasena']),
            'debe_cambiar_password' => false,
        ])->save();

        if (request()->hasSession()) {
            request()->session()->put('password_hash_'.Filament::getAuthGuard(), $estudiante->getAuthPassword());
        }

        Notification::make()->title('Contraseña actualizada')->success()->send();

        $this->redirect(Dashboard::getUrl());
    }

    private function estudiante(): Estudiante
    {
        return Filament::auth()->user();
    }
}
