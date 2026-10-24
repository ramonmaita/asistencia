<?php

namespace App\Filament\App\Resources\Estudiantes\Tables;

use App\Actions\GenerarCarnets;
use App\Models\Estudiante;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstudiantesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('secciones'))
            ->columns([
                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombres')
                    ->searchable(),
                TextColumn::make('secciones.codigo')
                    ->label('Secciones')
                    ->badge(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('debe_cambiar_password')
                    ->label('Clave inicial')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('activo')
                    ->boolean(),
            ])
            ->defaultSort('apellidos')
            ->filters([
                SelectFilter::make('secciones')
                    ->label('Sección')
                    ->relationship('secciones', 'codigo'),
                TernaryFilter::make('activo'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('restablecerContrasena')
                        ->label('Restablecer contraseña')
                        ->icon(Heroicon::OutlinedKey)
                        ->requiresConfirmation()
                        ->modalDescription('La contraseña volverá a ser la cédula y deberá cambiarla al entrar.')
                        ->action(function (Estudiante $record): void {
                            $record->restablecerContrasena();
                            Notification::make()->title('Contraseña restablecida')->success()->send();
                        }),
                    Action::make('regenerarQr')
                        ->label('Regenerar QR')
                        ->icon(Heroicon::OutlinedQrCode)
                        ->requiresConfirmation()
                        ->modalDescription('El carnet anterior dejará de funcionar. Úsalo si el estudiante perdió su carnet.')
                        ->action(function (Estudiante $record): void {
                            $record->regenerarQr();
                            Notification::make()->title('QR regenerado')->success()->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('imprimirCarnets')
                    ->label('Imprimir carnets')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records, GenerarCarnets $generarCarnets): StreamedResponse => response()->streamDownload(
                        fn () => print ($generarCarnets->handle($records)),
                        'carnets.pdf',
                        ['Content-Type' => 'application/pdf'],
                    )),
            ]);
    }
}
