<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnf_id')->constrained()->restrictOnDelete();
            $table->string('cedula', 20)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('password');
            $table->boolean('debe_cambiar_password')->default(true);
            $table->uuid('qr_token')->unique();
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['pnf_id', 'apellidos']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
