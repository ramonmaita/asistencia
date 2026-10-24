<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPnf;
use Database\Factories\EstudianteFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['pnf_id', 'cedula', 'nombres', 'apellidos', 'email', 'telefono', 'password', 'debe_cambiar_password', 'activo'])]
#[Hidden(['password', 'remember_token', 'qr_token'])]
class Estudiante extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<EstudianteFactory> */
    use BelongsToPnf, HasFactory, Notifiable;

    protected $attributes = [
        'debe_cambiar_password' => true,
        'activo' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (Estudiante $estudiante) {
            $estudiante->qr_token ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'debe_cambiar_password' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellidos}, {$this->nombres}";
    }

    public function getFilamentName(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'estudiante' && $this->activo;
    }
}
