<?php

namespace App\Filament\App\Resources\Cursos\Pages;

use App\Actions\RegistrarAsistencias;
use App\Enums\EstadoAsistencia;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\SesionClase;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Registro manual de la asistencia de una sesión: lista a todos los inscritos
 * de la sección (por defecto presentes) y al guardar marca la sesión como realizada.
 */
class RegistrarAsistencia extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CursoResource::class;

    protected static ?string $breadcrumb = 'Asistencia';

    #[Locked]
    public SesionClase $sesionClase;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(int|string $record, int|string $sesion): void
    {
        $this->record = $this->resolveRecord($record);
        $this->sesionClase = $this->getCurso()->sesiones()->findOrFail($sesion);

        $this->authorizeAccess();

        $this->form->fill([
            'estados' => collect(app(RegistrarAsistencias::class)->estadosIniciales($this->sesionClase))
                ->map(fn (EstadoAsistencia $estado): string => $estado->value)
                ->all(),
            'observacion' => $this->sesionClase->observacion,
        ]);
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('registrarAsistencia', $this->sesionClase), 403);
    }

    public function getTitle(): string
    {
        return 'Asistencia · '.$this->getCurso()->unidadCurricular->nombre;
    }

    public function getSubheading(): string
    {
        $sesion = $this->sesionClase;

        return "Sección {$this->getCurso()->seccion->codigo} · {$sesion->fechaLarga()} · {$sesion->horario()}";
    }

    /**
     * @return Collection<int, Estudiante>
     */
    #[Computed]
    public function inscritos(): Collection
    {
        return $this->getCurso()->estudiantes()->orderBy('apellidos')->orderBy('nombres')->get();
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
                Section::make('Estudiantes inscritos')
                    ->description(fn (): string => $this->inscritos->isEmpty()
                        ? 'La sección no tiene estudiantes inscritos.'
                        : "{$this->inscritos->count()} estudiantes. Todos aparecen como presentes hasta que cambies su estado.")
                    ->statePath('estados')
                    ->schema(fn (): array => $this->inscritos
                        ->map(fn (Estudiante $estudiante): ToggleButtons => ToggleButtons::make((string) $estudiante->getKey())
                            ->label("{$estudiante->nombre_completo} ({$estudiante->cedula})")
                            ->inlineLabel()
                            ->options(EstadoAsistencia::class)
                            ->inline()
                            ->grouped()
                            ->required())
                        ->all()),
                Textarea::make('observacion')
                    ->label('Observación de la clase')
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
            $this->sesionClase,
            collect($datos['estados'] ?? [])
                ->mapWithKeys(fn (EstadoAsistencia|string $estado, int|string $estudianteId): array => [
                    (int) $estudianteId => $estado instanceof EstadoAsistencia ? $estado : EstadoAsistencia::from($estado),
                ])
                ->all(),
            auth()->user(),
        );

        $this->sesionClase->update(['observacion' => $datos['observacion']]);

        Notification::make()->title('Asistencia guardada')->success()->send();

        $this->redirect(CursoResource::getUrl('view', ['record' => $this->getCurso()]));
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

    private function getCurso(): Curso
    {
        return $this->getRecord();
    }
}
