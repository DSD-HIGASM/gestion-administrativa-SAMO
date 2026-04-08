<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Config\UsuariosController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

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

    });

    Route::middleware(['auth', 'verified'])->prefix('samo')->name('samo.')->group(function () {

        // Bandeja y Expediente de Guardia
        Route::middleware(['permission:ver-gestion-guardia|facturar-guardia|dev'])->group(function () {
            Volt::route('/guardia', 'samo.bandeja-guardia')->name('guardia.inbox');
            Volt::route('/guardia/expediente/{tramite:ulid}', 'samo.expediente-guardia')->name('guardia.expediente');
        });

        // Bandeja y Expediente de Ambulatorio
        Route::middleware(['permission:ver-gestion-ambulatorio|facturar-ambulatorio-baja|facturar-ambulatorio-alta|dev'])->group(function () {
            Volt::route('/ambulatorio', 'samo.bandeja-ambulatorio')->name('ambulatorio.inbox');
            Volt::route('/ambulatorio/expediente/{tramite:ulid}', 'samo.expediente-ambulatorio')->name('ambulatorio.expediente');
        });

    });

    // Aquí agregaremos luego las rutas para Servicios, Nomencladores y Excel...
});

// Rutas de autenticación (Login / Logout)
require __DIR__.'/auth.php';
