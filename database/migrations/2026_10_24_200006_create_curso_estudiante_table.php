<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inscripción de estudiantes en cada unidad curricular (curso). Dos
     * estudiantes de la misma sección pueden cursar unidades distintas.
     *
     * Se llena con los inscritos de cada sección en todos sus cursos, que
     * era el comportamiento anterior.
     */
    public function up(): void
    {
        Schema::create('curso_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['curso_id', 'estudiante_id']);
        });

        DB::statement('
            insert into curso_estudiante (curso_id, estudiante_id, created_at, updated_at)
            select cursos.id, estudiante_seccion.estudiante_id, estudiante_seccion.created_at, estudiante_seccion.updated_at
            from cursos
            inner join estudiante_seccion on estudiante_seccion.seccion_id = cursos.seccion_id
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_estudiante');
    }
};
