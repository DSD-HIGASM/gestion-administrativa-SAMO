<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos_importados', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('nombre_original');
            $table->string('ruta_servidor');
            $table->string('origen_datos'); // Guardia, Ambulatorio, etc.
            $table->string('estado_procesamiento')->default('pendiente'); // pendiente, procesando, completado, con_errores

            // [Inferencia] Columnas agregadas para tracking y feedback
            $table->uuid('batch_id')->nullable(); // ID del lote de jobs de Laravel
            $table->integer('total_filas')->nullable(); // Total a procesar
            $table->integer('total_filas_procesadas')->default(0); // Progreso actual
            $table->json('errores')->nullable(); // Para auditar filas fallidas

            $table->foreignUlid('subido_por_usuario_ulid')->nullable()->constrained('users', 'ulid')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos_importados'); // Corregido el nombre de la tabla en el drop
    }
};
