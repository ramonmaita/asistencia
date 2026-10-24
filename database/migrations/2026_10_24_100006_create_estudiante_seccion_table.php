<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inscripción de estudiantes en secciones.
     */
    public function up(): void
    {
        Schema::create('estudiante_seccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('secciones')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['seccion_id', 'estudiante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_seccion');
    }
};
