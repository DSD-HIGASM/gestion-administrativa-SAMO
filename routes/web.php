<?php

use App\Http\Controllers\Config\UsuariosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// [Inferencia] Redirección absoluta al sistema.
// La validación de sesión queda a cargo del middleware 'auth'.
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// [Inferencia] Grupo de rutas protegidas para usuarios autenticados
Route::middleware(['auth'])->group(function () {

    // Panel de Control Principal
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // [Especulación] Rutas de Configuración exclusivas para Administradores
    Route::middleware(['permission:config'])->group(function () {

        // Pantalla de configuración general
        Route::get('/config', function () {
            return view('config.index');
        })->name('config.index');

        //Pantalla de usuarios
        Route::get('/config/usuarios', [UsuariosController::class, 'index'])
            ->name('config.usuarios');

    });

    // Aquí agregaremos luego las rutas para Servicios, Nomencladores y Excel...
});

// Rutas de autenticación (Login / Logout)
require __DIR__.'/auth.php';
