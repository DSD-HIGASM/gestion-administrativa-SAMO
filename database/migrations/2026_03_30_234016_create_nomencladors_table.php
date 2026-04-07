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
        Schema::create('nomencladores', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('cobertura');
            $table->string('codigo_practica')->index();
            $table->text('descripcion');
            $table->decimal('valor_unitario', 15, 2);
            $table->date('vigencia_desde')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomencladors');
    }
};
