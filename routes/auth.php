<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación (SAMO)
|--------------------------------------------------------------------------
|
| Aquí se definen exclusivamente las rutas para el ingreso y egreso
| de los operadores del sistema. La gestión de contraseñas y usuarios
| se realiza internamente por administradores, por lo que se omiten
| las rutas de registro y recuperación.
|
*/

// ========================================================================
// RUTAS PARA USUARIOS NO AUTENTICADOS (GUEST)
// ========================================================================
Route::middleware('guest')->group(function () {

    // [Inferencia] Muestra la vista del formulario de inicio de sesión institucional
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // [No verificado] Define la raíz del sistema directamente hacia el login
    Route::get('', [AuthenticatedSessionController::class, 'create']);

    // [Inferencia] Procesa la petición POST con el número de documento y la contraseña
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});


// ========================================================================
// RUTAS PARA USUARIOS AUTENTICADOS (AUTH)
// ========================================================================
Route::middleware('auth')->group(function () {

    // [Inferencia] Destruye la sesión del operador de forma segura y redirige al inicio
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
