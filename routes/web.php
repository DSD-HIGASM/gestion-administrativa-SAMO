<?php

use App\Http\Controllers\Config\UsuariosController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Samo\GuardiaController;
use App\Http\Controllers\Samo\AmbulatorioController;

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

        Route::get('/configuracion/ingesta', function () {
            return view('config.ingesta');
        })->name('config.ingesta');

        Route::get('/configuracion/nomencladores', [App\Http\Controllers\NomencladorController::class, 'index'])
            ->name('config.nomencladores');

        Route::get('/configuracion/jefatura', function () {
            return view('config.jefatura');
        })->name('config.jefatura');

    });

    Route::middleware(['auth', 'verified'])->prefix('samo')->name('samo.')->group(function () {

        // Bandeja de Guardia
        Route::middleware(['permission:ver-gestion-guardia|facturar-guardia|dev'])->group(function () {
            Route::get('/guardia', [GuardiaController::class, 'index'])->name('guardia.index');
            Route::get('/guardia/expediente/{tramite:ulid}', [GuardiaController::class, 'expediente'])->name('guardia.expediente'); // <-- AGREGAR ESTA LÍNEA
        });

        // Bandeja de Ambulatorio
        Route::middleware(['permission:ver-gestion-ambulatorio|facturar-ambulatorio-baja|facturar-ambulatorio-alta|dev'])->group(function () {
            Route::get('/ambulatorio', [AmbulatorioController::class, 'index'])->name('ambulatorio.index'); // <-- Cambiado de inbox a index
        });

    });
});

// Rutas de autenticación (Login / Logout)
require __DIR__.'/auth.php';
