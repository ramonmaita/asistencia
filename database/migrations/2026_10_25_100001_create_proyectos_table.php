<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proyecto socio tecnológico/integrador de un curso de la UC Proyecto.
     * Sus tutores académicos son los docentes de ese curso.
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
            $table->string('tutor_comunitario')->nullable();
            $table->string('tutor_comunitario_contacto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
