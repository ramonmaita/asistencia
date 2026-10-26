<?php

namespace App\Filament\App\Resources\Personal\Tables;

use App\Enums\RolPnf;
use App\Filament\App\Resources\Personal\Schemas\PersonalForm;
use App\Filament\App\Support\AccionesDeCuenta;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PersonalTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('rol_en_pnf')
                    ->label('Rol')
                    ->state(fn (User $record): ?RolPnf => RolPnf::tryFrom((string) $record->rol_en_pnf))
                    ->badge(),
                IconColumn::make('activo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('rol')
                    ->label('Rol')
                    ->options(RolPnf::class)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $rol) => $query->whereHas('pnfs', fn (Builder $pnfs) => $pnfs
                            ->whereKey(app(PnfActual::class)->id())
                            ->where('pnf_user.rol', $rol)),
                    )),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    static::cambiarRolAction(),
                    AccionesDeCuenta::cambiarContrasena(),
                    AccionesDeCuenta::activarODesactivar(),
                    static::quitarAction(),
                ]),
            ]);
    }

    private static function cambiarRolAction(): Action
    {
        return Action::make('cambiarRol')
            ->label('Cambiar rol')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->authorize('asignarRol')
            ->fillForm(fn (User $record): array => ['rol' => $record->rol_en_pnf])
            ->schema([PersonalForm::campoRol()])
            ->action(function (User $record, array $data): void {
                $record->pnfs()->updateExistingPivot(app(PnfActual::class)->id(), ['rol' => $data['rol']]);

                Notification::make()->title('Rol actualizado')->success()->send();
            });
    }

    /**
     * Lo saca del PNF sin borrar su cuenta ni lo que registró.
     */
    private static function quitarAction(): Action
    {
        return Action::make('quitar')
            ->label('Quitar del PNF')
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('danger')
            ->authorize('asignarRol')
            ->requiresConfirmation()
            ->modalDescription('La cuenta no se elimina: deja de tener acceso a este PNF y conserva su historial.')
            ->action(function (User $record): void {
                $record->pnfs()->detach(app(PnfActual::class)->id());

                Notification::make()->title("{$record->name} ya no forma parte del PNF")->success()->send();
            });
    }
}
