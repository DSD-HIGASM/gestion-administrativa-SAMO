<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('tipo_documento')->nullable();

            $table->text('documento');
            $table->text('nombre_completo');
            $table->text('fecha_nacimiento')->nullable();

            $table->string('genero_autopercibido')->nullable();
            $table->text('telefono')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
