<?php

namespace App\Filament\App\Resources\Seccions\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Estudiantes inscritos en la sección. La lista de opciones solo incluye
 * estudiantes del PNF actual gracias al PnfScope.
 *
 * Al inscribir se puede inscribir también en todas las unidades curriculares
 * de la sección; al retirar, el estudiante sale de ellas.
 */
class EstudiantesRelationManager extends RelationManager
{
    protected static string $relationship = 'estudiantes';

    /**
     * Filament adivinaría "seccions"; la relación en Estudiante es "secciones".
     */
    protected static ?string $inverseRelationship = 'secciones';

    protected static ?string $title = 'Estudiantes inscritos';

    protected static ?string $modelLabel = 'estudiante';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn ($record): string => "{$record->cedula} · {$record->nombre_completo}")
            ->columns([
                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombres')
                    ->searchable(),
            ])
            ->defaultSort('apellidos')
            ->headerActions([
                AttachAction::make()
                    ->label('Inscribir estudiantes')
                    ->modalHeading('Inscribir estudiantes')
                    ->modalSubmitActionLabel('Inscribir')
                    ->attachAnother(false)
                    ->successNotificationTitle('Estudiantes inscritos')
                    ->multiple()
                    ->recordSelectSearchColumns(['cedula', 'nombres', 'apellidos'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Toggle::make('inscribir_en_cursos')
                            ->label('Inscribir también en todas las unidades curriculares de la sección')
                            ->helperText('Desactívalo si cursarán solo algunas; luego puedes inscribirlos curso por curso.')
                            ->default(true),
                    ])
                    ->after(function (array $data): void {
                        if ($data['inscribir_en_cursos'] ?? false) {
                            $this->getOwnerRecord()->inscribirEnSusCursos(array_map('intval', (array) $data['recordId']));
                        }
                    }),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Retirar')
                    ->modalDescription('También se retira de las unidades curriculares de la sección. Su historial de asistencia se conserva.')
                    ->after(fn (Model $record) => $this->getOwnerRecord()->retirarDeSusCursos([$record->getKey()])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Retirar seleccionados')
                        ->modalDescription('También se retiran de las unidades curriculares de la sección. Su historial de asistencia se conserva.')
                        ->after(fn (Collection $records) => $this->getOwnerRecord()->retirarDeSusCursos($records->modelKeys())),
                ]),
            ]);
    }
}
