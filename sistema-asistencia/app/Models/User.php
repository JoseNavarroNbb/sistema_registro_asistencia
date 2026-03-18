<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'usuarios';

    /**
     * Atributos que son asignables en masa
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'contrasena',
        'rol',
        'estado'
    ];

    /**
     * Atributos que deben estar ocultos para arrays/serialización
     */
    protected $hidden = [
        'contrasena',
        'remember_token'
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Obtener el nombre completo del usuario
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Mutador para encriptar automáticamente la contraseña
     */
    public function setContrasenaAttribute($value)
    {
        $this->attributes['contrasena'] = bcrypt($value);
    }

    /**
     * Relación: Un usuario puede tener un empleado asociado
     */
    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class, 'id_usuario', 'id');
    }

    /**
     * Scope para filtrar usuarios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', true);
    }

    /**
     * Scope para filtrar por rol
     */
    public function scopePorRol($query, $rol)
    {
        return $query->where('rol', $rol);
    }
}