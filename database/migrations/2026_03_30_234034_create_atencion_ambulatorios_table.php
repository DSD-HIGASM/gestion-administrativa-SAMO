<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atenciones_ambulatorio', function (Blueprint $table) {
            $table->ulid('ulid')->primary();

            $table->foreignUlid('paciente_ulid')->constrained('pacientes', 'ulid')->cascadeOnDelete();
            $table->foreignUlid('archivo_ulid')->nullable()->constrained('archivos_importados', 'ulid')->nullOnDelete();

            $table->string('hash_atencion')->unique();

            $table->string('apellidos');
            $table->string('nombres');
            $table->enum('tipo_documento', ['DNI','CI','LC','LE','Cédula Mercosur','CUIT','CUIL','Pasaporte extranjero','Cédula de identidad extranjera','Otro documento extranjero','No posee','En trámite'])->default('DNI');
            $table->string('numero_documento')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->integer('telefono')->nullable();
            $table->text('obra_social')->nullable();
            $table->string('numero_afiliado')->nullable();
            $table->date('fecha_atencion');
            $table->string('especialidad');
            $table->text('profesional');
            $table->text('problema')->nullable();
            $table->text('practicas_estudios')->nullable();
            $table->text('procedimientos')->nullable();



            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atenciones_ambulatorio');
    }
};
