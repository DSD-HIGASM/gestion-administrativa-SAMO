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
        Schema::create('atenciones_guardia', function (Blueprint $table) {
            $table->ulid('ulid')->primary();

            $table->foreignUlid('paciente_ulid')->constrained('pacientes', 'ulid')->cascadeOnDelete();
            $table->foreignUlid('archivo_ulid')->nullable()->constrained('archivos_importados', 'ulid')->nullOnDelete();
            $table->foreignUlid('hsi_especialidad_ulid')->nullable()->constrained('hsi_especialidades', 'ulid')->nullOnDelete();
            $table->foreignUlid('nomenclador_ulid')->nullable()->constrained('nomencladores', 'ulid')->nullOnDelete();

            $table->string('id_episodio_hsi')->unique();
            $table->timestamp('fecha_apertura')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            $table->timestamp('fecha_alta_medica')->nullable();

            $table->string('obra_social_episodio')->nullable();
            $table->text('numero_afiliado')->nullable();

            $table->text('diagnosticos')->nullable();
            $table->string('codigo_cie10_asignado')->nullable();
            $table->string('estado_samo')->default('pendiente');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atencion_guardias');
    }
};
