<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AutenticacionController;


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
    Route::post('/auth/iniciar-sesion', [AutenticacionController::class, 'iniciarSesion']);
    Route::post('/auth/registrar-usuario', [AutenticacionController::class, 'registrar']);
    Route::post('/auth/recuperar-password', [AutenticacionController::class, 'enviarCorreoRecuperacion']);
    Route::post('/auth/restablecer-password', [AutenticacionController::class, 'restablecerPassword']);
    



/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
| Todas estas rutas requieren token de autenticación válido
*/
Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'v1'], function () {
    
});