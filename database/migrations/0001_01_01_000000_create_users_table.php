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
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->integer('document')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // [Inferencia] Se elimina la tabla password_reset_tokens ya que no se usará email.
        // La gestión de contraseñas será interna a través del sistema.

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            // [No verificado] Cambiamos a foreignUlid para que coincida con el tipo de la tabla users.
            // Mantenemos el nombre 'user_id' para no romper la lógica interna de sesiones de Laravel.
            $table->foreignUlid('user_id')->nullable()->index();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
