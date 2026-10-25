<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada veredicto registrado (o cambiado) para un integrante del proyecto.
     */
    public function up(): void
    {
        Schema::create('historial_veredictos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('integrante_proyecto_id')->constrained('integrantes_proyecto')->cascadeOnDelete();
            $table->string('veredicto_anterior', 20)->nullable();
            $table->string('veredicto', 20);
            $table->text('observacion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_veredictos');
    }
};
