<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RestablecerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo' => 'required|email|max:150',
            'token' => 'required|string',
            'contrasena' => 'required|string|min:8|confirmed',
            'contrasena_confirmacion' => 'required|string'
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'token.required' => 'El token de recuperación es obligatorio.',
            'contrasena.required' => 'La nueva contraseña es obligatoria.',
            'contrasena.min' => 'La contraseña debe tener al menos :min caracteres.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden.'
        ];
    }
}