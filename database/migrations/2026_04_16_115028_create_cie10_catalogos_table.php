<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cie10_catalogos', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('codigo')->unique(); // Ej: 'A00.0'
            $table->text('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cie10_catalogos');
    }
};
