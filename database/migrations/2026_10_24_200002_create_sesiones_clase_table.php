<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada clase concreta (fecha y hora) de un curso. Se generan a partir del
     * horario semanal y también pueden crearse sesiones extra.
     */
    public function up(): void
    {
        Schema::create('sesiones_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('estado', 20)->default('programada');
            $table->string('tipo', 20)->default('regular');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['curso_id', 'fecha', 'hora_inicio']);
            $table->index(['pnf_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_clase');
    }
};
