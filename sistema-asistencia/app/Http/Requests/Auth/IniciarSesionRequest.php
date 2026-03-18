<?php
/**
 * Request para Inicio de Sesión
 * 
 * Archivo: app/Http/Requests/Auth/IniciarSesionRequest.php
 */

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class IniciarSesionRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado a hacer esta request
     */
    public function authorize(): bool
    {
        return true; // Cualquiera puede intentar iniciar sesión
    }

    /**
     * Reglas de validación para el inicio de sesión
     */
    public function rules(): array
    {
        return [
            'correo' => [
                'required',
                'email',
                'max:150',
                'exists:usuarios,correo' // Verifica que el correo exista en la BD
            ],
            'contrasena' => [
                'required',
                'string',
                'min:8'
            ]
        ];
    }

    /**
     * Mensajes personalizados para las reglas de validación
     */
    public function messages(): array
    {
        return [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Debe proporcionar un correo electrónico válido.',
            'correo.exists' => 'Las credenciales proporcionadas son incorrectas.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.min' => 'La contraseña debe tener al menos :min caracteres.'
        ];
    }

    /**
     * Manejar la validación fallida
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Error de validación',
                'detalles' => $validator->errors()
            ], 422)
        );
    }
}