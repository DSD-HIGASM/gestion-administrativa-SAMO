<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // En Laravel la convención es el plural en inglés, pero si quieres 'exclusiones'
        // está bien, luego le decimos al modelo cómo se llama.
        Schema::create('exclusiones', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            // Ej: "Servicio Guardia", "Servicio Ambulatorio", "Profesional"
            $table->string('tipo_exclusion');
            // Ej: "Enfermeria", "Oftalmologia", "Lopez, Juan"
            $table->string('valor_exacto');
            // Notas del Jefe de por qué se excluye esto
            $table->string('motivo_exclusion')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            // Indice para búsquedas más rápidas en el importador
            $table->index(['tipo_exclusion', 'valor_exacto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exclusiones');
    }
};
