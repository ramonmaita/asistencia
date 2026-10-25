<?php

namespace App\Filament\App\Resources\Proyectos\RelationManagers;

use App\Actions\AgregarIntegrantes;
use App\Actions\CalcularInasistencias;
use App\Actions\ResumenInasistencia;
use App\Enums\NivelInasistencia;
use App\Enums\VeredictoProyecto;
use App\Filament\App\Resources\Proyectos\ProyectoResource;
use App\Models\Estudiante;
use App\Models\IntegranteProyecto;
use App\Models\Proyecto;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Integrantes del proyecto con su asistencia a tutorías y su veredicto
 * (con el historial de cambios).
 */
class IntegrantesRelationManager extends RelationManager
{
    protected static string $relationship = 'integrantes';

    protected static ?string $title = 'Integrantes';

    protected static ?string $modelLabel = 'integrante';

    /** @var Collection<int, ResumenInasistencia>|null */
    private ?Collection $resumenes = null;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ProyectoResource::canView($ownerRecord);
    }

    /**
     * El proyecto se ve en solo lectura, pero el tutor gestiona los
     * integrantes y sus veredictos desde aquí (cada acción tiene su política).
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function getOwnerRecord(): Proyecto
    {
        return parent::getOwnerRecord();
    }

    private function resumen(IntegranteProyecto $integrante): ResumenInasistencia
    {
        $this->resumenes ??= app(CalcularInasistencias::class)->deProyecto($this->getOwnerRecord());

        return $this->resumenes->get($integrante->estudiante_id)
            ?? app(CalcularInasistencias::class)->deProyecto($this->getOwnerRecord(), $integrante->estudiante)->first();
    }

    private function puedeEditar(): bool
    {
        return (bool) auth()->user()?->can('update', $this->getOwnerRecord());
    }

    public function table(Table $table): Table
    {
        $trayecto = $this->getOwnerRecord()->trayecto();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['estudiante', 'autorDelVeredicto'])->withCount('historialVeredictos'))
            ->recordTitle(fn (IntegranteProyecto $record): string => $record->estudiante->nombre_completo)
            ->description(fn (): string => $trayecto->min_integrantes === $trayecto->max_integrantes
                ? "Modalidad {$trayecto->modalidad_proyecto->getLabel()}: {$trayecto->max_integrantes} integrante(s). Límite de inasistencia a tutorías: {$this->umbral()}."
                : "Modalidad {$trayecto->modalidad_proyecto->getLabel()}: de {$trayecto->min_integrantes} a {$trayecto->max_integrantes} integrantes. Límite de inasistencia a tutorías: {$this->umbral()}.")
            ->columns([
                TextColumn::make('estudiante.cedula')
                    ->label('Cédula'),
                TextColumn::make('estudiante.nombre_completo')
                    ->label('Estudiante'),
                TextColumn::make('tutorias')
                    ->label('Tutorías')
                    ->state(fn (IntegranteProyecto $record): int => $this->resumen($record)->sesiones),
                TextColumn::make('inasistencias')
                    ->state(fn (IntegranteProyecto $record): int => $this->resumen($record)->ausentes),
                TextColumn::make('porcentaje')
                    ->label('% inasistencia')
                    ->state(fn (IntegranteProyecto $record): string => $this->resumen($record)->porcentajeFormateado()),
                TextColumn::make('nivel')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (IntegranteProyecto $record) => $this->resumen($record)->nivel),
                TextColumn::make('veredicto')
                    ->badge()
                    ->placeholder('Pendiente')
                    ->description(fn (IntegranteProyecto $record): ?string => $record->autorDelVeredicto ? "Por {$record->autorDelVeredicto->name}" : null),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderBy(
                Estudiante::select('apellidos')->whereColumn('estudiantes.id', 'integrantes_proyecto.estudiante_id'),
            ))
            ->headerActions([
                Action::make('agregar')
                    ->label('Agregar integrantes')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->visible(fn (): bool => $this->puedeEditar())
                    ->modalHeading('Agregar integrantes')
                    ->modalDescription('Estudiantes inscritos en el curso que aún no pertenecen a ningún proyecto.')
                    ->modalSubmitActionLabel('Agregar')
                    ->schema([
                        Select::make('estudiantes')
                            ->label('Estudiantes')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => $this->getOwnerRecord()->curso->estudiantes()
                                ->whereDoesntHave('proyectos', fn (Builder $proyectos) => $proyectos->where('proyectos.curso_id', $this->getOwnerRecord()->curso_id))
                                ->orderBy('apellidos')
                                ->get()
                                ->mapWithKeys(fn (Estudiante $estudiante): array => [$estudiante->id => "{$estudiante->cedula} · {$estudiante->nombre_completo}"])
                                ->all())
                            ->required(),
                    ])
                    ->action(function (array $data, AgregarIntegrantes $agregar): void {
                        $agregar->handle($this->getOwnerRecord(), $data['estudiantes'], campo: 'mountedActions.0.data.estudiantes');
                        $this->resumenes = null;

                        Notification::make()->title('Integrantes agregados')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('veredicto')
                    ->label('Veredicto')
                    ->icon(Heroicon::OutlinedScale)
                    ->visible(fn (): bool => (bool) auth()->user()?->can('registrarVeredicto', $this->getOwnerRecord()))
                    ->modalHeading(fn (IntegranteProyecto $record): string => "Veredicto de {$record->estudiante->nombre_completo}")
                    ->modalDescription(fn (IntegranteProyecto $record): ?string => $this->resumen($record)->nivel === NivelInasistencia::Superado
                        ? "Sugerido: Reprobado. Supera el límite de inasistencia a tutorías ({$this->resumen($record)->porcentajeFormateado()} de {$this->umbral()})."
                        : null)
                    ->fillForm(fn (IntegranteProyecto $record): array => [
                        'veredicto' => ($record->veredicto ?? ($this->resumen($record)->nivel === NivelInasistencia::Superado ? VeredictoProyecto::Reprobado : null))?->value,
                        'observacion' => $record->observacion_veredicto,
                    ])
                    ->schema([
                        ToggleButtons::make('veredicto')
                            ->options(VeredictoProyecto::class)
                            ->inline()
                            ->required(),
                        Textarea::make('observacion')
                            ->label('Observación')
                            ->maxLength(1000),
                    ])
                    ->action(function (IntegranteProyecto $record, array $data): void {
                        $veredicto = $data['veredicto'] instanceof VeredictoProyecto ? $data['veredicto'] : VeredictoProyecto::from($data['veredicto']);
                        $record->registrarVeredicto($veredicto, auth()->user(), $data['observacion'] ?? null);

                        Notification::make()->title('Veredicto registrado')->success()->send();
                    }),
                Action::make('historial')
                    ->label('Historial')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->visible(fn (IntegranteProyecto $record): bool => $record->historial_veredictos_count > 0)
                    ->modalHeading(fn (IntegranteProyecto $record): string => "Historial de veredictos de {$record->estudiante->nombre_completo}")
                    ->modalContent(fn (IntegranteProyecto $record): View => view('filament.app.historial-veredictos', [
                        'historial' => $record->historialVeredictos()->with('autor')->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->slideOver(),
                DeleteAction::make()
                    ->label('Quitar')
                    ->modalHeading('Quitar del proyecto')
                    ->modalDescription('Su asistencia a las tutorías ya registradas se conserva.')
                    ->visible(fn (IntegranteProyecto $record): bool => $this->puedeEditar() && $record->veredicto === null),
            ]);
    }

    private function umbral(): string
    {
        return number_format((float) $this->getOwnerRecord()->trayecto()->umbral_inasistencia_tutorias, 2, ',', '.').' %';
    }
}
