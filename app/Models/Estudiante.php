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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
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

            if (blank($estudiante->password)) {
                $estudiante->password = static::hashContrasenaInicial($estudiante->cedula);
                $estudiante->debe_cambiar_password = true;
            }
        });
    }

    /**
     * Vuelve la contraseña a la cédula y obliga a cambiarla en el próximo ingreso.
     */
    public function restablecerContrasena(): void
    {
        $this->forceFill([
            'password' => static::hashContrasenaInicial($this->cedula),
            'debe_cambiar_password' => true,
        ])->save();
    }

    /**
     * La contraseña inicial (la cédula) es temporal, así que se cifra con menos
     * rondas para que importar cientos de estudiantes no tarde minutos.
     * Laravel la vuelve a cifrar con el costo normal al iniciar sesión.
     */
    private static function hashContrasenaInicial(string $cedula): string
    {
        return Hash::make($cedula, ['rounds' => min(10, (int) config('hashing.bcrypt.rounds', 12))]);
    }

    /**
     * Texto codificado en el QR del carnet.
     */
    public function contenidoQr(): string
    {
        return "GPNF:{$this->qr_token}";
    }

    /**
     * Invalida el QR anterior (p. ej. por pérdida del carnet).
     */
    public function regenerarQr(): void
    {
        $this->forceFill(['qr_token' => (string) Str::uuid()])->save();
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'debe_cambiar_password' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function secciones(): BelongsToMany
    {
        return $this->belongsToMany(Seccion::class)->withTimestamps();
    }

    /**
     * Unidades curriculares (cursos) que cursa.
     */
    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class)->withTimestamps();
    }

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'integrantes_proyecto')->withTimestamps();
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function justificaciones(): HasMany
    {
        return $this->hasMany(Justificacion::class);
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
