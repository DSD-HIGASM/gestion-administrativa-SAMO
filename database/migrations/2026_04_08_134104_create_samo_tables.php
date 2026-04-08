<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Motor de Estados
        Schema::create('samo_estados', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('nombre');
            $table->string('color_hex')->default('#CCCCCC');
            $table->boolean('es_estado_inicial')->default(false);
            $table->boolean('es_estado_final')->default(false);
            $table->integer('orden_logico')->default(0);
            $table->timestamps();
        });

        // 2. El Trámite Central
        Schema::create('samo_tramites', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('codigo_visual')->unique(); // El código inteligente

            $table->foreignUlid('paciente_ulid')->constrained('pacientes', 'ulid');
            $table->foreignUlid('atencion_guardia_ulid')->nullable()->constrained('atenciones_guardia', 'ulid')->nullOnDelete();
            $table->foreignUlid('atencion_ambulatorio_ulid')->nullable()->constrained('atenciones_ambulatorio', 'ulid')->nullOnDelete();
            $table->foreignUlid('estado_ulid')->constrained('samo_estados', 'ulid');
            $table->foreignUlid('asignado_a_usuario_ulid')->nullable()->constrained('users', 'ulid')->nullOnDelete();

            $table->string('obra_social_facturada')->nullable();
            $table->string('numero_afiliado_facturado')->nullable();
            $table->string('numero_bono_autorizacion')->nullable();

            $table->decimal('monto_total_calculado', 10, 2)->default(0);
            $table->date('fecha_vencimiento_presentacion')->nullable();
            $table->text('observaciones_internas')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Borrado lógico
        });

        // 3. Prácticas y Valores
        Schema::create('samo_tramite_practicas', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->foreignUlid('samo_tramite_ulid')->constrained('samo_tramites', 'ulid')->cascadeOnDelete();

            $table->enum('nomenclador_origen', ['SAMO', 'PAMI', 'IOMA', 'CUSTOM'])->default('SAMO');
            $table->string('codigo_practica');
            $table->text('descripcion_practica')->nullable();
            $table->integer('cantidad')->default(1);
            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->decimal('valor_subtotal', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Diagnósticos (CIE-10)
        Schema::create('samo_tramite_diagnosticos', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->foreignUlid('samo_tramite_ulid')->constrained('samo_tramites', 'ulid')->cascadeOnDelete();

            $table->string('cie10_codigo');
            $table->enum('tipo_diagnostico', ['Principal', 'Secundario', 'Presuntivo'])->default('Principal');

            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Documentos
        Schema::create('samo_documentos', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->foreignUlid('paciente_ulid')->constrained('pacientes', 'ulid')->cascadeOnDelete();
            $table->foreignUlid('samo_tramite_ulid')->nullable()->constrained('samo_tramites', 'ulid')->cascadeOnDelete();

            $table->enum('categoria', ['DNI', 'Credencial_OS', 'Orden_Medica', 'Bono_Consulta', 'Receta', 'Otro'])->default('Otro');
            $table->string('ruta_archivo');
            $table->string('nombre_original');

            $table->timestamps();
            $table->softDeletes();
        });

        // 6. Auditoría (La caja negra)
        Schema::create('samo_auditorias', function (Blueprint $table) {
            $table->id(); // BigInteger para orden cronológico perfecto
            $table->foreignUlid('samo_tramite_ulid')->constrained('samo_tramites', 'ulid')->cascadeOnDelete();
            $table->foreignUlid('usuario_ulid')->nullable()->constrained('users', 'ulid')->nullOnDelete();

            $table->string('accion');
            $table->json('detalles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samo_auditorias');
        Schema::dropIfExists('samo_documentos');
        Schema::dropIfExists('samo_tramite_diagnosticos');
        Schema::dropIfExists('samo_tramite_practicas');
        Schema::dropIfExists('samo_tramites');
        Schema::dropIfExists('samo_estados');
    }
};
