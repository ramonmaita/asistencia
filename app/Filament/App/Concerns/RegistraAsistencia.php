<?php

namespace App\Filament\App\Concerns;

use App\Actions\RegistrarAsistencias;
use App\Enums\EstadoAsistencia;
use App\Models\Contracts\Sesionable;
use App\Models\Estudiante;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

/**
 * Formulario de registro manual de asistencia de una sesión (clase o
 * tutoría): lista a todos los convocados, por defecto presentes, y al
 * guardar marca la sesión como realizada.
 */
trait RegistraAsistencia
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    abstract protected function sesionable(): Sesionable;

    abstract protected function urlDeRegreso(): string;

    protected function llenarFormulario(): void
    {
        $this->authorizeAccess();

        $this->form->fill([
            'estados' => collect(app(RegistrarAsistencias::class)->estadosIniciales($this->sesionable()))
                ->map(fn (EstadoAsistencia $estado): string => $estado->value)
                ->all(),
            'observacion' => $this->sesionable()->observacion,
        ]);
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('registrarAsistencia', $this->sesionable()), 403);
    }

    /**
     * @return Collection<int, Estudiante>
     */
    #[Computed]
    public function inscritos(): Collection
    {
        return $this->sesionable()->participantes()->orderBy('apellidos')->orderBy('nombres')->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->marcarTodosAction('todosPresentes', EstadoAsistencia::Presente),
            $this->marcarTodosAction('todosAusentes', EstadoAsistencia::Ausente),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Estudiantes')
                    ->description(fn (): string => $this->inscritos->isEmpty()
                        ? 'No hay estudiantes convocados a esta sesión.'
                        : "{$this->inscritos->count()} estudiantes. Todos aparecen como presentes hasta que cambies su estado.")
                    ->statePath('estados')
                    ->schema(fn (): array => $this->inscritos
                        ->map(fn (Estudiante $estudiante): ToggleButtons => ToggleButtons::make((string) $estudiante->getKey())
                            ->label("{$estudiante->nombre_completo} ({$estudiante->cedula})")
                            ->inlineLabel()
                            ->options(EstadoAsistencia::class)
                            ->inline()
                            ->grouped()
                            ->required()
                            ->markAsRequired(false))
                        ->all()),
                Textarea::make('observacion')
                    ->label('Observación')
                    ->rows(2)
                    ->maxLength(1000),
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
                                ->label('Guardar asistencia')
                                ->submit('guardar'),
                        ]),
                    ]),
            ]);
    }

    public function guardar(RegistrarAsistencias $registrar): void
    {
        $datos = $this->form->getState();

        $registrar->handle(
            $this->sesionable(),
            collect($datos['estados'] ?? [])
                ->mapWithKeys(fn (EstadoAsistencia|string $estado, int|string $estudianteId): array => [
                    (int) $estudianteId => $estado instanceof EstadoAsistencia ? $estado : EstadoAsistencia::from($estado),
                ])
                ->all(),
            auth()->user(),
        );

        $this->sesionable()->update(['observacion' => $datos['observacion']]);

        Notification::make()->title('Asistencia guardada')->success()->send();

        $this->redirect($this->urlDeRegreso());
    }

    private function marcarTodosAction(string $nombre, EstadoAsistencia $estado): Action
    {
        return Action::make($nombre)
            ->label('Todos '.mb_strtolower($estado->getLabel()).'s')
            ->icon($estado === EstadoAsistencia::Presente ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
            ->color($estado->getColor())
            ->outlined()
            ->action(function () use ($estado): void {
                $this->data['estados'] = $this->inscritos
                    ->mapWithKeys(fn (Estudiante $estudiante): array => [$estudiante->getKey() => $estado->value])
                    ->all();
            });
    }
}
