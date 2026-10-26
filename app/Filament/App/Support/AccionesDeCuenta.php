<?php

namespace App\Filament\App\Support;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Acciones sobre la cuenta de un usuario del personal, comunes al panel de
 * administración y a la gestión del personal de cada PNF.
 */
final class AccionesDeCuenta
{
    public static function cambiarContrasena(): Action
    {
        return Action::make('cambiarContrasena')
            ->label('Cambiar contraseña')
            ->icon(Heroicon::OutlinedKey)
            ->authorize('update')
            ->modalDescription('Comunícale la nueva contraseña al usuario; podrá cambiarla luego desde su perfil.')
            ->schema([
                TextInput::make('password')
                    ->label('Nueva contraseña')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(),
            ])
            ->action(function (User $record, array $data): void {
                $record->update(['password' => $data['password']]);

                Notification::make()->title('Contraseña actualizada')->success()->send();
            });
    }

    /**
     * Una cuenta inactiva no entra a ningún panel ni a la app.
     */
    public static function activarODesactivar(): Action
    {
        return Action::make('activarODesactivar')
            ->label(fn (User $record): string => $record->activo ? 'Desactivar' : 'Activar')
            ->icon(fn (User $record): Heroicon => $record->activo ? Heroicon::OutlinedNoSymbol : Heroicon::OutlinedCheckCircle)
            ->color(fn (User $record): string => $record->activo ? 'danger' : 'success')
            ->authorize('update')
            ->hidden(fn (User $record): bool => $record->is(auth()->user()))
            ->requiresConfirmation()
            ->action(function (User $record): void {
                $record->update(['activo' => ! $record->activo]);
                $record->tokens()->delete();

                Notification::make()->title($record->activo ? 'Cuenta activada' : 'Cuenta desactivada')->success()->send();
            });
    }
}
