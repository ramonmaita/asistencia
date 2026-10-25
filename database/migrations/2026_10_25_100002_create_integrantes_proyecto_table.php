<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Integrantes de cada proyecto con su veredicto individual. El curso se
     * repite aquí para garantizar que un estudiante esté en un solo proyecto
     * por curso.
     */
    public function up(): void
    {
        Schema::create('integrantes_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained()->cascadeOnDelete();
            $table->string('veredicto', 20)->nullable();
            $table->text('observacion_veredicto')->nullable();
            $table->foreignId('veredicto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('veredicto_at')->nullable();
            $table->timestamps();

            $table->unique(['curso_id', 'estudiante_id']);
            $table->index('proyecto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrantes_proyecto');
    }
};
