<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hsi_especialidades', function (Blueprint $table) {
            $table->ulid('ulid')->primary();

            // Texto crudo tal cual llega en el Excel de HSI
            $table->string('nombre_crudo_hsi')->unique();

            // Relación con tu tabla de Servicios reales (puede estar vacía al principio)
            $table->foreignUlid('service_ulid')->nullable()->constrained('services', 'ulid')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hsi_especialidades');
    }
};
