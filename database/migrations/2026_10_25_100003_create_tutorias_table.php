<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tutorías de un proyecto. El tutor las registra cuando se reúne con el
     * grupo; su asistencia va en la tabla asistencias (sesionable "tutoria")
     * y se controla aparte de la de clases.
     */
    public function up(): void
    {
        Schema::create('tutorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('tema');
            $table->string('estado', 20)->default('programada');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['proyecto_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorias');
    }
};
