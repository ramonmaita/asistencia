<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes de justificación de una inasistencia, hechas por el estudiante
     * y revisadas por el docente del curso o el coordinador.
     */
    public function up(): void
    {
        Schema::create('justificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('asistencia_id')->constrained()->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained()->cascadeOnDelete();
            $table->text('motivo');
            $table->string('soporte_path');
            $table->string('soporte_nombre');
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('revisada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisada_at')->nullable();
            $table->text('observacion_revision')->nullable();
            $table->timestamps();

            $table->index(['pnf_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justificaciones');
    }
};
