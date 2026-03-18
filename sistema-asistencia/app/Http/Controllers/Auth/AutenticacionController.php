<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IniciarSesionRequest;
use App\Http\Requests\Auth\RegistrarUsuarioRequest;
use App\Http\Requests\Auth\RecuperarPasswordRequest;
use App\Http\Requests\Auth\RestablecerPasswordRequest;
use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Models\obtenerUsuarioAutenticado;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AutenticacionController extends Controller
{
    /**
     * @var array Mensajes de respuesta constantes
     */
    private const MENSAJES = [
        'credenciales_incorrectas' => 'El correo o contraseña son incorrectos.',
        'usuario_inactivo' => 'La cuenta está inactiva. Contacte al administrador.',
        'sesion_cerrada' => 'Sesión cerrada exitosamente.',
        'token_refrescado' => 'Token refrescado exitosamente.',
        'password_actualizada' => 'Contraseña actualizada correctamente.',
        'password_incorrecta' => 'La contraseña actual no es correcta.',
        'registro_exitoso' => 'Usuario registrado exitosamente.',
        'correo_enviado' => 'Se ha enviado un correo con las instrucciones para recuperar tu contraseña.',
        'password_restablecida' => 'Contraseña restablecida correctamente.',
        'token_invalido' => 'El token de recuperación no es válido o ha expirado.',
        'error_general' => 'Ocurrió un error al procesar la solicitud.'
    ];

    /**
     * ===========================================================
     * MÉTODOS PÚBLICOS (SIN AUTENTICACIÓN)
     * ===========================================================
     */

    /**
     * Iniciar sesión en el sistema
     * 
     * @param IniciarSesionRequest $request
     * @return JsonResponse
     */
    public function iniciarSesion(IniciarSesionRequest $request): JsonResponse
    {
        try {
            $credenciales = $request->only('correo', 'contrasena');
            
            if (!Auth::attempt($credenciales)) {
                return $this->errorResponse(self::MENSAJES['credenciales_incorrectas'], 401);
            }

            $usuario = User::where('correo', $request->correo)->first();

            if (!$usuario->estado) {
                Auth::logout();
                return $this->errorResponse(self::MENSAJES['usuario_inactivo'], 403);
            }

            // Revocar tokens anteriores y crear nuevo
            $usuario->tokens()->delete();
            $token = $usuario->createToken('auth_token', [$usuario->rol])->plainTextToken;

            $datosUsuario = $this->formatearDatosUsuario($usuario);

            return response()->json([
                'success' => true,
                'data' => [
                    'acceso' => [
                        'token' => $token,
                        'tipo' => 'Bearer',
                        'expira_en' => config('sanctum.expiration') * 60
                    ],
                    'usuario' => $datosUsuario
                ],
                'mensaje' => "Bienvenido {$usuario->nombre}"
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en inicio de sesión: ' . $e->getMessage());
            return $this->errorResponse(self::MENSAJES['error_general'], 500);
        }
    }

    /**
     * Registrar un nuevo usuario en el sistema
     * 
     * @param RegistrarUsuarioRequest $request
     * @return JsonResponse
     */
    public function registrar(RegistrarUsuarioRequest $request): JsonResponse
    {
        try {
            // Crear el usuario
            $usuario = User::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'correo' => $request->correo,
                'contrasena' => Hash::make($request->contrasena),
                'rol' => 'empleado', 
                'estado' => true
            ]);

            // NOTA: Aquí podrías crear automáticamente el registro en empleados
            // Pero como requiere datos adicionales (departamento, cargo, etc.),
            // es mejor que el admin complete esos datos después

            Log::info("Nuevo usuario registrado: {$usuario->correo}");

            return response()->json([
                'success' => true,
                'mensaje' => self::MENSAJES['registro_exitoso'],
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'apellido' => $usuario->apellido,
                        'correo' => $usuario->correo,
                        'rol' => $usuario->rol
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en registro de User: ' . $e->getMessage());
            return $this->errorResponse(self::MENSAJES['error_general'], 500);
        }
    }

    /**
     * Enviar correo de recuperación de contraseña
     * 
     * @param RecuperarPasswordRequest $request
     * @return JsonResponse
     */
    public function enviarCorreoRecuperacion(RecuperarPasswordRequest $request): JsonResponse
    {
        try {
            // Buscar el usuario
            $usuario = User::where('correo', $request->correo)->first();
            
            if (!$usuario) {
                // Por seguridad, no revelamos si el correo existe o no
                return response()->json([
                    'success' => true,
                    'mensaje' => self::MENSAJES['correo_enviado']
                ]);
            }

            // Generar token de recuperación
            $token = Str::random(60);
            
            // Guardar token en la tabla password_reset_tokens
            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->correo],
                [
                    'email' => $request->correo,
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Aquí enviarías el correo electrónico
            // Por ahora solo simulamos el envío
            Log::info("Token de recuperación para {$request->correo}: {$token}");

            // TODO: Implementar envío de correo real
            // Mail::to($usuario->correo)->send(new RecuperarPasswordMailable($token));

            return response()->json([
                'success' => true,
                'mensaje' => self::MENSAJES['correo_enviado'],
                // Solo en desarrollo devolvemos el token para pruebas
                'debug_token' => config('app.debug') ? $token : null
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar correo de recuperación: ' . $e->getMessage());
            return $this->errorResponse(self::MENSAJES['error_general'], 500);
        }
    }

    /**
     * Restablecer la contraseña usando el token
     * 
     * @param RestablecerPasswordRequest $request
     * @return JsonResponse
     */
    public function restablecerPassword(RestablecerPasswordRequest $request): JsonResponse
    {
        try {
            // Buscar el token en la tabla
            $resetToken = \DB::table('password_reset_tokens')
                ->where('email', $request->correo)
                ->first();

            if (!$resetToken || !Hash::check($request->token, $resetToken->token)) {
                return $this->errorResponse(self::MENSAJES['token_invalido'], 400);
            }

            // Verificar que el token no haya expirado (60 minutos)
            $expiration = config('auth.passwords.users.expire', 60);
            if (now()->diffInMinutes($resetToken->created_at) > $expiration) {
                \DB::table('password_reset_tokens')->where('email', $request->correo)->delete();
                return $this->errorResponse(self::MENSAJES['token_invalido'], 400);
            }

            // Actualizar la contraseña del usuario
            $usuario = User::where('correo', $request->correo)->first();
            $usuario->contrasena = Hash::make($request->contrasena);
            $usuario->save();

            // Eliminar el token usado
            \DB::table('password_reset_tokens')->where('email', $request->correo)->delete();

            // Revocar todos los tokens del usuario por seguridad
            $usuario->tokens()->delete();

            Log::info("Contraseña restablecida para: {$request->correo}");

            return response()->json([
                'success' => true,
                'mensaje' => self::MENSAJES['password_restablecida']
            ]);

        } catch (\Exception $e) {
            Log::error('Error al restablecer contraseña: ' . $e->getMessage());
            return $this->errorResponse(self::MENSAJES['error_general'], 500);
        }
    }

    /**
     * ===========================================================
     * MÉTODOS PROTEGIDOS (REQUIEREN AUTENTICACIÓN)
     * ===========================================================
     */

    /**
     * Cerrar sesión del usuario
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cerrarSesion(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'mensaje' => self::MENSAJES['sesion_cerrada']
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al cerrar sesión: ' . $e->getMessage());
            return $this->errorResponse(self::MENSAJES['error_general'], 500);
        }
    }

    /**
     * Obtener usuario autenticado
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function obtenerUsuarioAutenticado(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            if ($usuario->rol === 'empleado') {
                $usuario->load(['empleado' => function($query) {
                    $query->with('departamento');
                }]);
            }
            
            $datosUsuario = $this->formatearDatosUsuario($usuario);
            
            return response()->json([
                'success' => true,
                'data' => $datosUsuario
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener User: ' . $e->getMessage());
            return $this->errorResponse(self::MENSAJES['error_general'], 500);
        }
    }

    /**
     * Refrescar token de autenticación
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refrescarToken(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            // Revocar token actual
            $usuario->currentAccessToken()->delete();
            
            // Crear nuevo token
            $nuevoToken = $usuario->createToken('auth_token', [$usuario->rol])->plainTextToken;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $nuevoToken,
                    'tipo' => 'Bearer',
                    'expira_en' => config('sanctum.expiration') * 60
                ],
                'mensaje' => self::MENSAJES['token_refrescado']
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al refrescar token: ' . $e->getMessage());
            return $this->errorResponse(self::MENSAJES['error_general'], 500);
        }
    }

    /**
     * Cambiar contraseña (usuario autenticado)
     * 
     * @param CambiarPasswordRequest $request
     * @return JsonResponse
     */
    public function cambiarPassword(CambiarPasswordRequest $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            // Verificar contraseña actual
            if (!Hash::check($request->contrasena_actual, $usuario->contrasena)) {
                return $this->errorResponse(self::MENSAJES['password_incorrecta'], 400);
            }
            
            // Actualizar contraseña
            $usuario->contrasena = Hash::make($request->contrasena_nueva);
            $usuario->save();
            
            // Opcional: revocar otros tokens excepto el actual
            $usuario->tokens()->where('id', '!=', $usuario->currentAccessToken()->id)->delete();
            
            Log::info("Contraseña cambiada para User: {$usuario->correo}");
            
            return response()->json([
                'success' => true,
                'mensaje' => self::MENSAJES['password_actualizada']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al cambiar contraseña: ' . $e->getMessage());
            return $this->errorResponse(self::MENSAJES['error_general'], 500);
        }
    }

    /**
     * ===========================================================
     * MÉTODOS PRIVADOS (UTILIDADES)
     * ===========================================================
     */

    /**
     * Formatear datos del usuario para respuesta
     * 
     * @param Usuario $usuario
     * @return array
     */
    private function formatearDatosUsuario(Usuario $usuario): array
    {
        $datos = [
            'id' => $usuario->id,
            'nombre_completo' => $usuario->nombre . ' ' . $usuario->apellido,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'correo' => $usuario->correo,
            'rol' => $usuario->rol,
            'estado' => $usuario->estado
        ];

        if ($usuario->rol === 'empleado' && $usuario->empleado) {
            $datos['empleado'] = [
                'id' => $usuario->empleado->id,
                'codigo' => $usuario->empleado->codigo_empleado,
                'cargo' => $usuario->empleado->cargo,
                'departamento' => $usuario->empleado->departamento->nombre ?? null,
                'fecha_ingreso' => $usuario->empleado->fecha_ingreso ? $usuario->empleado->fecha_ingreso->format('Y-m-d') : null
            ];
        }

        return $datos;
    }

    /**
     * Respuesta de error estandarizada
     * 
     * @param string $mensaje
     * @param int $codigo
     * @return JsonResponse
     */
    private function errorResponse(string $mensaje, int $codigo): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $mensaje
        ], $codigo);
    }
}