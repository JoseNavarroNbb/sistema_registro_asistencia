<?php


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IniciarSesionRequest;
use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Models\Usuario;
use App\Models\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AutenticacionControlador extends Controller
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
        'error_general' => 'Ocurrió un error al procesar la solicitud.'
    ];

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

            $usuario = Usuario::where('correo', $request->correo)->first();

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
            Log::error('Error al obtener usuario: ' . $e->getMessage());
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
                'fecha_ingreso' => $usuario->empleado->fecha_ingreso
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