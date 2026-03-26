<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AutenticacionController extends Controller
{
    /**
     * Iniciar sesión en el sistema
     */
    public function iniciarSesion(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required'
        ]);

        $usuario = User::where('correo', $request->correo)->first();

        if (!$usuario || !Hash::check($request->contrasena, $usuario->contrasena)) {
            return response()->json([
                'mensaje' => 'Las credenciales proporcionadas son incorrectas.'
            ], 401);
        }

        if (!$usuario->estado) {
            return response()->json([
                'mensaje' => 'Su cuenta se encuentra inactiva o dada de baja.'
            ], 403);
        }
        
        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Sesión iniciada correctamente',
            'token' => $token,
            'usuario' => $usuario,
        ], 200);
    }

    /**
     * Cerrar sesión del usuario autenticado
     */
    public function cerrarSesion(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente'
        ], 200);
    }

    /**
     * Obtener los datos del usuario autenticado
     */
    public function usuarioAutenticado(Request $request)
    {
        return response()->json([
            'usuario' => $request->user()
        ], 200);
    }

    /**
     * Registrar un nuevo usuario en el sistema
     */
    public function registrar(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo' => 'required|string|email|max:150|unique:usuarios,correo',
            'contrasena' => 'required|string|min:6',
            'rol' => 'required|in:admin,empleado'
        ]);

        $usuario = User::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'correo' => $request->correo,
            // modelo User encripta la contraseña
            'contrasena' => $request->contrasena,
            'rol' => $request->rol,
            'estado' => true
        ]);

        return response()->json([
            'mensaje' => 'Usuario registrado exitosamente',
            'usuario' => $usuario
        ], 201);
    }

    /**
     * Enviar correo de recuperación
     */
    public function enviarCorreoRecuperacion(Request $request)
    {
        // Pendiente---Implementar lógica de envío de correos
        return response()->json([
            'mensaje' => 'Funcionalidad de recuperación en desarrollo'
        ], 501);
    }

    /**
     * Restablecer la contraseña
     */
    public function restablecerPassword(Request $request)
    {
        // Pendiente---lógica de restablecimiento de contraseña
        return response()->json([
            'mensaje' => 'Funcionalidad de restablecimiento en desarrollo'
        ], 501);
    }


    /**
     * Obtener todos los usuarios activos
     */
    public function obtenerUsuarios()
    {
        // El scope SoftDeletes descarta los eliminados lógicamente
        $usuarios = User::all();

        return response()->json([
            'usuarios' => $usuarios
        ], 200);
    }

    /**
     * Obtener el detalle de un usuario específico
     */
    public function obtenerUsuario($id)
    {
        $usuario = User::findOrFail($id);

        return response()->json([
            'usuario' => $usuario
        ], 200);
    }

    /**
     * Actualizar los datos de un usuario
     */
    public function actualizarUsuario(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            // Validar que el nuevo correo no esté tomado por otro usuario diferente
            'correo' => 'sometimes|string|email|max:150|unique:usuarios,correo,' . $id,
            'contrasena' => 'sometimes|string|min:6',
            'rol' => 'sometimes|in:admin,empleado',
            'estado' => 'sometimes|boolean'
        ]);

        // Evitamos enviar la contraseña al fill para usar el asignador manual e invocar el mutador
        if ($request->has('contrasena')) {
            $usuario->contrasena = $request->contrasena;
        }

        $usuario->fill($request->except('contrasena'));
        $usuario->save();

        return response()->json([
            'mensaje' => 'Usuario actualizado exitosamente',
            'usuario' => $usuario
        ], 200);
    }

    /**
     * Dar de baja a un usuario (Eliminación lógica)
     */
    public function eliminarUsuario($id)
    {
        $usuario = User::findOrFail($id);

        // Desactivamos al usuario
        $usuario->estado = false;
        $usuario->save();

        // Aplicamos el Soft Delete (llena deleted_at)
        $usuario->delete();

        return response()->json([
            'mensaje' => 'Usuario dado de baja exitosamente (Eliminado lógicamente)'
        ], 200);
    }
}