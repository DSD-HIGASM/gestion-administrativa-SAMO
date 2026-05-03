<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('clave')->unique(); // Ej: "FECHA_MAESTRA_PAMI"
            $table->text('valor')->nullable(); // Ej: "2026-12-31"
            $table->string('descripcion')->nullable();
            $table->string('tipo_dato')->default('string'); // 'string', 'boolean', 'json', 'date'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
