<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomencladors', function (Blueprint $table) {
            $table->ulid('ulid')->primary();

            $table->enum('origen', ['SAMO', 'PAMI', 'IOMA', 'CUSTOM'])->default('SAMO');
            $table->string('codigo');
            $table->text('descripcion');

            // Nullable porque PAMI o IOMA pueden no tener un valor fijo establecido
            $table->decimal('valor', 10, 2)->nullable();

            // Para "apagar" prácticas viejas sin borrar el historial
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índice compuesto para búsquedas ultra rápidas
            $table->index(['origen', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomencladors');
    }
};
