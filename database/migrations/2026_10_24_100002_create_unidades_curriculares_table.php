<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_curriculares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('trayecto_id')->constrained()->restrictOnDelete();
            $table->string('codigo', 30);
            $table->string('nombre');
            $table->unsignedTinyInteger('horas_semanales')->nullable();
            $table->boolean('es_proyecto')->default(false);
            $table->timestamps();

            $table->unique(['pnf_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_curriculares');
    }
};
