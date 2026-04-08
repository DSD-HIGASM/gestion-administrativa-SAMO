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

            $table->integer('id_paciente_hsi');
            $table->string('apellidos');
            $table->string('nombres');
            $table->enum('tipo_documento', ['DNI','CI','LC','LE','Cédula Mercosur','CUIT','CUIL','Pasaporte extranjero','Cédula de identidad extranjera','Otro documento extranjero','No posee','En trámite'])->default('DNI');
            $table->string('numero_documento')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['Masculino','Femenino','X'])->nullable();
            $table->string('telefono')->nullable();
            $table->text('obra_social')->nullable();
            $table->string('numero_afiliado')->nullable();

            $table->integer('id_episodio');
            $table->datetime('fecha_hora_apertura')->nullable();
            $table->integer('cantidad_triage')->nullable();
            $table->string('servicio');
            $table->datetime('fecha_hora_atencion')->nullable();
            $table->text('diagnostico')->nullable();
            $table->datetime('fecha_hora_alta')->nullable();
            $table->text('practicas_procedimientos')->nullable();
            $table->text('profesional')->nullable();
            $table->text('profesional_alta')->nullable();
            $table->string('tipo_egreso')->nullable();

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
