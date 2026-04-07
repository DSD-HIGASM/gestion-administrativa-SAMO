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
        Schema::create('atenciones_ambulatorio', function (Blueprint $table) {
            $table->ulid('ulid')->primary();

            $table->foreignUlid('paciente_ulid')->constrained('pacientes', 'ulid')->cascadeOnDelete();
            $table->foreignUlid('archivo_ulid')->nullable()->constrained('archivos_importados', 'ulid')->nullOnDelete();
            $table->foreignUlid('hsi_especialidad_ulid')->nullable()->constrained('hsi_especialidades', 'ulid')->nullOnDelete();
            $table->foreignUlid('nomenclador_ulid')->nullable()->constrained('nomencladores', 'ulid')->nullOnDelete();

            $table->string('hash_atencion')->unique();
            $table->date('fecha_atencion')->nullable();
            $table->string('profesional')->nullable();

            $table->string('obra_social_paciente')->nullable();
            $table->text('numero_afiliado')->nullable();

            $table->text('problemas_salud_diagnostico')->nullable();
            $table->text('practicas_estudios')->nullable();

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
        Schema::dropIfExists('atencion_ambulatorios');
    }
};
