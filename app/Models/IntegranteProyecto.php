<?php

namespace App\Models;

use App\Enums\VeredictoProyecto;
use App\Models\Concerns\BelongsToPnf;
use Database\Factories\IntegranteProyectoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Estudiante integrante de un proyecto, con su veredicto individual.
 */
#[Fillable(['pnf_id', 'proyecto_id', 'curso_id', 'estudiante_id'])]
class IntegranteProyecto extends Model
{
    /** @use HasFactory<IntegranteProyectoFactory> */
    use BelongsToPnf, HasFactory;

    protected $table = 'integrantes_proyecto';

    protected function casts(): array
    {
        return [
            'veredicto' => VeredictoProyecto::class,
            'veredicto_at' => 'datetime',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function autorDelVeredicto(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veredicto_por');
    }

    /**
     * Cambios del veredicto, del más reciente al más antiguo.
     */
    public function historialVeredictos(): HasMany
    {
        return $this->hasMany(HistorialVeredicto::class)->latest()->latest('id');
    }

    /**
     * Registra o cambia el veredicto y deja constancia en el historial.
     */
    public function registrarVeredicto(VeredictoProyecto $veredicto, User $tutor, ?string $observacion = null): void
    {
        DB::transaction(function () use ($veredicto, $tutor, $observacion) {
            $this->historialVeredictos()->create([
                'pnf_id' => $this->pnf_id,
                'veredicto_anterior' => $this->veredicto,
                'veredicto' => $veredicto,
                'observacion' => $observacion,
                'registrado_por' => $tutor->getKey(),
            ]);

            $this->forceFill([
                'veredicto' => $veredicto,
                'observacion_veredicto' => $observacion,
                'veredicto_por' => $tutor->getKey(),
                'veredicto_at' => now(),
            ])->save();
        });
    }
}
