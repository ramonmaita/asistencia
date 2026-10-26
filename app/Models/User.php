<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RolPnf;
use App\Support\PnfActual;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'cedula', 'email', 'password', 'is_superadmin', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = [
        'is_superadmin' => false,
        'activo' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function pnfs(): BelongsToMany
    {
        return $this->belongsToMany(Pnf::class)
            ->using(PnfUser::class)
            ->withPivot('rol')
            ->withTimestamps();
    }

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class)->withTimestamps();
    }

    /**
     * Indica si coordina el PNF de la petición en curso (o es superadmin).
     */
    public function coordinaPnfActual(): bool
    {
        $pnfId = app(PnfActual::class)->id();

        if ($this->is_superadmin) {
            return true;
        }

        return $pnfId !== null
            && $this->pnfs->firstWhere('id', $pnfId)?->pivot->rol === RolPnf::Coordinador;
    }

    /**
     * Indica si gestiona los datos del PNF de la petición en curso:
     * coordinador, administrativo o superadmin.
     */
    public function administraPnfActual(): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        return in_array($this->rolEnPnfActual(), [RolPnf::Coordinador, RolPnf::Administrativo], true);
    }

    public function rolEnPnfActual(): ?RolPnf
    {
        $pnfId = app(PnfActual::class)->id();

        return $pnfId === null ? null : $this->pnfs->firstWhere('id', $pnfId)?->pivot->rol;
    }

    public function rolEn(Pnf $pnf): ?RolPnf
    {
        return $this->pnfs->firstWhere('id', $pnf->getKey())?->pivot->rol;
    }

    public function esCoordinadorDe(Pnf $pnf): bool
    {
        return $this->is_superadmin || $this->rolEn($pnf) === RolPnf::Coordinador;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->activo) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->is_superadmin,
            'app' => $this->is_superadmin || $this->pnfs()->exists(),
            default => false,
        };
    }

    public function getTenants(Panel $panel): array|Collection
    {
        return $this->is_superadmin
            ? Pnf::where('activo', true)->orderBy('nombre')->get()
            : $this->pnfs()->where('activo', true)->orderBy('nombre')->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->is_superadmin || $this->pnfs()->whereKey($tenant)->exists();
    }
}
