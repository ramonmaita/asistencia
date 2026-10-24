<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una sección es el grupo de estudiantes de un trayecto en un periodo.
     */
    public function up(): void
    {
        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('trayecto_id')->constrained()->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained()->restrictOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained()->nullOnDelete();
            $table->string('codigo', 30);
            $table->string('turno', 10)->nullable();
            $table->timestamps();

            $table->unique(['pnf_id', 'periodo_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones');
    }
};
