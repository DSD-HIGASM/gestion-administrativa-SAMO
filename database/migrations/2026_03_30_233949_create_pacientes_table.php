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
            $table->string('apellidos');
            $table->string('nombres');
            $table->enum('tipo_documento', ['DNI','CI','LC','LE','Cédula Mercosur','CUIT','CUIL','Pasaporte extranjero','Cédula de identidad extranjera','Otro documento extranjero','No posee','En trámite'])->default('DNI');
            $table->string('documento')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono')->nullable();
            $table->json('obras_sociales')->nullable();
            $table->integer('id_paciente_hsi')->nullable();
            $table->enum('sexo', ['Masculino','Femenino','X'])->nullable();

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
