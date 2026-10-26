<?php

namespace App\Filament\App\Resources\Personal\Pages;

use App\Filament\App\Resources\Personal\PersonalResource;
use App\Models\User;
use App\Support\PnfActual;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePersonal extends CreateRecord
{
    protected static string $resource = PersonalResource::class;

    /**
     * Crea la cuenta y la asigna al PNF actual con el rol elegido.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $rol = $data['rol'];
        unset($data['rol']);

        return DB::transaction(function () use ($data, $rol): User {
            $usuario = User::create($data);
            $usuario->pnfs()->attach(app(PnfActual::class)->id(), ['rol' => $rol]);

            return $usuario;
        });
    }
}
