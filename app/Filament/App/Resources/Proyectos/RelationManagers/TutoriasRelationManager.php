<?php

namespace App\Filament\App\Resources\Proyectos\RelationManagers;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoSesion;
use App\Filament\App\Resources\Proyectos\ProyectoResource;
use App\Models\Proyecto;
use App\Models\Tutoria;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tutorías del proyecto. El tutor registra cada reunión con el grupo y
 * toma la asistencia de los integrantes.
 */
class TutoriasRelationManager extends RelationManager
{
    protected static string $relationship = 'tutorias';

    protected static ?string $title = 'Tutorías';

    protected static ?string $modelLabel = 'tutoría';

    protected static ?string $pluralModelLabel = 'tutorías';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ProyectoResource::canView($ownerRecord);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getOwnerRecord(): Proyecto
    {
        return parent::getOwnerRecord();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tema')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                DatePicker::make('fecha')
                    ->default(today())
                    ->required(),
                Select::make('estado')
                    ->options(EstadoSesion::class)
                    ->required()
                    ->hiddenOn('create'),
                TimePicker::make('hora_inicio')
                    ->label('Desde')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('hora_fin')
                    ->label('Hasta')
                    ->seconds(false)
                    ->after('hora_inicio')
                    ->required(),
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
            ->recordTitle(fn (Tutoria $record): string => "{$record->tema} ({$record->fechaLarga()})")
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'asistencias',
                'asistencias as ausentes_count' => fn (Builder $asistencias) => $asistencias->where('estado', EstadoAsistencia::Ausente),
            ]))
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->description(fn (Tutoria $record): string => $record->horario())
                    ->sortable(),
                TextColumn::make('tema')
                    ->wrap(),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('asistencias_count')
                    ->label('Registrados'),
                TextColumn::make('ausentes_count')
                    ->label('Ausentes'),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('fecha')->orderByDesc('hora_inicio'))
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva tutoría')
                    ->modalHeading('Nueva tutoría')
                    ->successRedirectUrl(fn (Tutoria $record): ?string => auth()->user()->can('registrarAsistencia', $record)
                        ? ProyectoResource::getUrl('tutoria', ['record' => $this->getOwnerRecord(), 'tutoria' => $record])
                        : null),
            ])
            ->recordActions([
                Action::make('asistencia')
                    ->label('Asistencia')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->visible(fn (Tutoria $record): bool => auth()->user()->can('registrarAsistencia', $record))
                    ->url(fn (Tutoria $record): string => ProyectoResource::getUrl('tutoria', [
                        'record' => $this->getOwnerRecord(),
                        'tutoria' => $record,
                    ])),
                Action::make('qr')
                    ->label('QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('gray')
                    ->visible(fn (Tutoria $record): bool => $record->fecha->isToday() && auth()->user()->can('registrarAsistencia', $record))
                    ->url(fn (Tutoria $record): string => ProyectoResource::getUrl('qrTutoria', [
                        'record' => $this->getOwnerRecord(),
                        'tutoria' => $record,
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
