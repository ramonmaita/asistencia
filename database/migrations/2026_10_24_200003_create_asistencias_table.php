<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asistencia de un estudiante a una sesión. Es polimórfica para que las
     * tutorías del proyecto (Fase 4) usen la misma tabla.
     */
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->string('sesionable_type');
            $table->unsignedBigInteger('sesionable_id');
            $table->foreignId('estudiante_id')->constrained()->cascadeOnDelete();
            $table->string('estado', 20);
            $table->string('metodo', 20)->default('manual');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registrado_at')->nullable();
            $table->timestamps();

            $table->unique(['sesionable_type', 'sesionable_id', 'estudiante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
