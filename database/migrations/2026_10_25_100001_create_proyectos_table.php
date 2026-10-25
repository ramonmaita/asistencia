<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proyecto socio tecnológico/integrador de un curso de la UC Proyecto.
     * Tiene tres tutores: el académico (los docentes de ese curso), el
     * técnico (otro docente del PNF) y el institucional (persona externa,
     * sin acceso al sistema).
     */
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('comunidad')->nullable();
            $table->foreignId('tutor_tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tutor_institucional')->nullable();
            $table->string('tutor_institucional_contacto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
