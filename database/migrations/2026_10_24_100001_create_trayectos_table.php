<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trayectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->string('nombre');
            $table->string('modalidad_proyecto', 20);
            $table->unsignedTinyInteger('min_integrantes')->default(1);
            $table->unsignedTinyInteger('max_integrantes')->default(1);
            $table->decimal('umbral_inasistencia_tutorias', 5, 2)->default(25);
            $table->timestamps();

            $table->unique(['pnf_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trayectos');
    }
};
