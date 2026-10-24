<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de los avisos de inasistencia ya enviados, para no repetirlos.
     */
    public function up(): void
    {
        Schema::create('avisos_inasistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained()->cascadeOnDelete();
            $table->string('nivel', 20);
            $table->decimal('porcentaje', 5, 2);
            $table->timestamps();

            $table->unique(['curso_id', 'estudiante_id', 'nivel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_inasistencia');
    }
};
