<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas compartidas por toda la institución (sin pnf_id).
     */
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });

        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('descripcion');
            $table->timestamps();
        });

        Schema::create('pnfs', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo', 20)->unique();
            $table->string('slug')->unique();
            $table->decimal('umbral_inasistencia_clases', 5, 2)->default(25);
            $table->string('logo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('pnf_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rol', 20);
            $table->timestamps();

            $table->unique(['pnf_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pnf_user');
        Schema::dropIfExists('pnfs');
        Schema::dropIfExists('feriados');
        Schema::dropIfExists('periodos');
        Schema::dropIfExists('sedes');
    }
};
