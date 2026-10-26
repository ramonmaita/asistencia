<?php

namespace App\Filament\App\Resources\Personal\Pages;

use App\Filament\App\Resources\Personal\PersonalResource;
use App\Filament\App\Resources\Personal\Schemas\PersonalForm;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListPersonal extends ListRecords
{
    protected static string $resource = PersonalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->agregarExistenteAction(),
            CreateAction::make()->label('Nueva cuenta'),
        ];
    }

    /**
     * Suma al PNF a alguien que ya tiene cuenta (p. ej. un docente de otro PNF).
     */
    private function agregarExistenteAction(): Action
    {
        return Action::make('agregarExistente')
            ->label('Agregar personal existente')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('gray')
            ->visible(fn (): bool => PersonalResource::canCreate())
            ->modalDescription('Busca por nombre, cédula o correo a una persona que ya tiene cuenta en el sistema.')
            ->schema([
                Select::make('user_id')
                    ->label('Persona')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => $this->sinAsignar()
                        ->where(fn (Builder $usuarios) => $usuarios
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('cedula', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn (User $usuario): array => [$usuario->id => "{$usuario->name} ({$usuario->cedula})"])
                        ->all())
                    ->getOptionLabelUsing(fn (int|string $value): ?string => User::find($value)?->name)
                    ->required(),
                PersonalForm::campoRol(),
            ])
            ->action(function (array $data): void {
                $usuario = $this->sinAsignar()->findOrFail($data['user_id']);
                $usuario->pnfs()->attach(app(PnfActual::class)->id(), ['rol' => $data['rol']]);

                Notification::make()->title("{$usuario->name} se agregó al PNF")->success()->send();
            });
    }

    /**
     * Cuentas activas que todavía no están en el PNF actual.
     *
     * @return Builder<User>
     */
    private function sinAsignar(): Builder
    {
        return User::query()
            ->where('activo', true)
            ->where('is_superadmin', false)
            ->whereDoesntHave('pnfs', fn (Builder $pnfs) => $pnfs->whereKey(app(PnfActual::class)->id()));
    }
}
