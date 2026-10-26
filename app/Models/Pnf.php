<?php

namespace App\Models;

use App\Enums\RolPnf;
use Database\Factories\PnfFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['nombre', 'codigo', 'slug', 'umbral_inasistencia_clases', 'logo', 'activo'])]
class Pnf extends Model implements HasName
{
    /** @use HasFactory<PnfFactory> */
    use HasFactory;

    protected $attributes = [
        'umbral_inasistencia_clases' => 25,
        'activo' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (Pnf $pnf) {
            if (blank($pnf->slug)) {
                $pnf->slug = Str::slug($pnf->codigo);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'umbral_inasistencia_clases' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->nombre;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(PnfUser::class)
            ->withPivot('rol')
            ->withTimestamps();
    }

    public function coordinadores(): BelongsToMany
    {
        return $this->users()->wherePivot('rol', RolPnf::Coordinador);
    }

    public function docentes(): BelongsToMany
    {
        return $this->users()->wherePivot('rol', RolPnf::Docente);
    }

    public function administrativos(): BelongsToMany
    {
        return $this->users()->wherePivot('rol', RolPnf::Administrativo);
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class);
    }

    public function trayectos(): HasMany
    {
        return $this->hasMany(Trayecto::class);
    }

    public function unidadesCurriculares(): HasMany
    {
        return $this->hasMany(UnidadCurricular::class);
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class);
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function justificaciones(): HasMany
    {
        return $this->hasMany(Justificacion::class);
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }
}
