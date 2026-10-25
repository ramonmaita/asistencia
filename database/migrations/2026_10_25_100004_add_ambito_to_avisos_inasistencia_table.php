<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los avisos de inasistencia distinguen entre clases y tutorías, que se
     * controlan por separado (cada una con su umbral).
     */
    public function up(): void
    {
        Schema::table('avisos_inasistencia', function (Blueprint $table) {
            $table->string('ambito', 20)->default('clases')->after('estudiante_id');
            $table->unique(['curso_id', 'estudiante_id', 'nivel', 'ambito'], 'avisos_inasistencia_unico');
        });

        Schema::table('avisos_inasistencia', function (Blueprint $table) {
            $table->dropUnique(['curso_id', 'estudiante_id', 'nivel']);
        });
    }

    public function down(): void
    {
        Schema::table('avisos_inasistencia', function (Blueprint $table) {
            $table->unique(['curso_id', 'estudiante_id', 'nivel']);
        });

        Schema::table('avisos_inasistencia', function (Blueprint $table) {
            $table->dropUnique('avisos_inasistencia_unico');
            $table->dropColumn('ambito');
        });
    }
};
