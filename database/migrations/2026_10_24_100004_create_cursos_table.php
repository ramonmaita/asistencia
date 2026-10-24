<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un curso es una unidad curricular dictada en una sección.
     */
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('seccion_id')->constrained('secciones')->cascadeOnDelete();
            $table->foreignId('unidad_curricular_id')->constrained('unidades_curriculares')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['seccion_id', 'unidad_curricular_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
