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
Route::group(['middleware' => 'auth:sanctum'], function ($router) {
    
    // Autenticación Protegida
    Route::post('/auth/cerrar-sesion', [AutenticacionController::class, 'cerrarSesion']);
    Route::get('/auth/usuario-autenticado', [AutenticacionController::class, 'usuarioAutenticado']);
    
    // CRUD Usuarios
    Route::get('/usuarios', [AutenticacionController::class, 'obtenerUsuarios']);
    Route::get('/usuarios/{id}', [AutenticacionController::class, 'obtenerUsuario']);
    Route::put('/usuarios/{id}', [AutenticacionController::class, 'actualizarUsuario']);
    Route::delete('/usuarios/{id}', [AutenticacionController::class, 'eliminarUsuario']); 

});