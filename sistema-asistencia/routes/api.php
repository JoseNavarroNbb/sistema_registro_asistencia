<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AutenticacionControlador;
use App\Http\Controllers\Auth\RegistroControlador;
use App\Http\Controllers\Auth\PasswordControlador;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
| Estas rutas no requieren autenticación

    /**
     * =========================================================
     * MÓDULO: Autenticación Pública
     * =========================================================
     */
    Route::post('/auth/iniciar-sesion', [AutenticacionControlador::class, 'iniciarSesion']);
    Route::post('/auth/registrar-usuario', [RegistroControlador::class, 'registrar']);
    Route::post('/auth/recuperar-password', [PasswordControlador::class, 'enviarCorreoRecuperacion']);
    Route::post('/auth/restablecer-password', [PasswordControlador::class, 'restablecerPassword']);
    
    /**
     * =========================================================
     * MÓDULO: Consultas Públicas
     * =========================================================
     */
    Route::get('/publico/departamentos', [DepartamentoControlador::class, 'obtenerDepartamentosActivos']);
    Route::get('/publico/cargos', [CargoControlador::class, 'obtenerCargos']);


/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
| Todas estas rutas requieren token de autenticación válido
*/
Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'v1'], function () {
    
});