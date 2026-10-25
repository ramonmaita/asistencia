<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubicación de la sede para validar por GPS el registro con QR dinámico.
     */
    public function up(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->decimal('latitud', 10, 7)->nullable()->after('direccion');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            $table->unsignedInteger('radio_metros')->nullable()->after('longitud');
        });
    }

    public function down(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud', 'radio_metros']);
        });
    }
};
