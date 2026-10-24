<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RolPnf;
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

#[Fillable(['name', 'cedula', 'email', 'password', 'is_superadmin', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
