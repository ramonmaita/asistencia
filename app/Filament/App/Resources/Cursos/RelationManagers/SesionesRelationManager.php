<?php

namespace App\Filament\App\Resources\Cursos\RelationManagers;

use App\Actions\GenerarSesionesClase;
use App\Enums\EstadoAsistencia;
use App\Enums\EstadoSesion;
use App\Enums\TipoSesion;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Models\Curso;
use App\Models\SesionClase;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Sesiones de clase del curso: desde aquí el docente registra la asistencia.
 */
class SesionesRelationManager extends RelationManager
{
    protected static string $relationship = 'sesiones';

    protected static ?string $title = 'Sesiones de clase';

    protected static ?string $modelLabel = 'sesión';

    protected static ?string $pluralModelLabel = 'sesiones';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return CursoResource::canView($ownerRecord);
    }

    /**
     * La página de ver curso deja las relaciones en solo lectura, pero el
     * docente gestiona sus sesiones desde aquí (cada acción tiene su política).
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('fecha')
                    ->required()
                    ->rule(fn (Get $get, ?SesionClase $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        if (blank($get('hora_inicio'))) {
                            return;
                        }

                        $repetida = $this->getOwnerRecord()->sesiones()
                            ->whereDate('fecha', $value)
                            ->whereTime('hora_inicio', Carbon::parse($get('hora_inicio'))->format('H:i:s'))
                            ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($repetida) {
                            $fail('Ya hay una sesión del curso en esa fecha y hora.');
                        }
                    }),
                TimePicker::make('hora_inicio')
                    ->label('Desde')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('hora_fin')
                    ->label('Hasta')
                    ->seconds(false)
                    ->after('hora_inicio')
                    ->required(),
                Select::make('estado')
                    ->options(EstadoSesion::class)
                    ->required()
                    ->hiddenOn('create'),
                Textarea::make('observacion')
                    ->label('Observación')
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (SesionClase $record): string => $record->fechaLarga())
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'asistencias',
                'asistencias as ausentes_count' => fn (Builder $asistencias) => $asistencias->where('estado', EstadoAsistencia::Ausente),
            ]))
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->description(fn (SesionClase $record): string => $record->horario())
                    ->sortable(),
                TextColumn::make('tipo')
                    ->badge(),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('asistencias_count')
                    ->label('Registrados'),
                TextColumn::make('ausentes_count')
                    ->label('Ausentes'),
                TextColumn::make('observacion')
                    ->label('Observación')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('fecha')->orderByDesc('hora_inicio'))
            ->filters([
                Filter::make('hasta_hoy')
                    ->label('Solo hasta hoy')
                    ->query(fn (Builder $query) => $query->whereDate('fecha', '<=', today()))
                    ->default(),
                SelectFilter::make('estado')
                    ->options(EstadoSesion::class),
            ])
            ->headerActions([
                Action::make('generarSesiones')
                    ->label('Generar sesiones')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color('gray')
                    ->visible(fn (): bool => auth()->user()->can('generarSesiones', $this->getOwnerRecord()))
                    ->requiresConfirmation()
                    ->modalDescription('Se crean las sesiones de todo el periodo según el horario semanal, saltando los feriados. Las sesiones existentes no se modifican.')
                    ->action(function (GenerarSesionesClase $generar): void {
                        $creadas = $generar->handle($this->getOwnerRecord());

                        Notification::make()
                            ->title($creadas > 0 ? "Se crearon {$creadas} sesiones" : 'No había sesiones nuevas que crear')
                            ->success()
                            ->send();
                    }),
                CreateAction::make()
                    ->label('Sesión extra')
                    ->modalHeading('Nueva sesión extra')
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'tipo' => TipoSesion::Extra]),
            ])
            ->recordActions([
                Action::make('asistencia')
                    ->label('Asistencia')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->visible(fn (SesionClase $record): bool => auth()->user()->can('registrarAsistencia', $record))
                    ->url(fn (SesionClase $record): string => CursoResource::getUrl('asistencia', [
                        'record' => $this->getOwnerRecord(),
                        'sesion' => $record,
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function getOwnerRecord(): Curso
    {
        return parent::getOwnerRecord();
    }
}
