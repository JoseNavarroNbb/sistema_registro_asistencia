<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Empleado extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'empleados';

    /**
     * Atributos que son asignables en masa
     *
     * @var array<string>
     */
    protected $fillable = [
        'id_usuario',
        'id_departamento',
        'cargo',
        'codigo_empleado',
        'fecha_ingreso'
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_ingreso' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Los atributos que deben ocultarse en las arrays
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at'
    ];

    /**
     * ===========================================================
     * RELACIONES
     * ===========================================================
     */

    /**
     * Un empleado pertenece a un usuario
     * Relación inversa a Usuario->empleado()
     *
     * @return BelongsTo
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    /**
     * Un empleado pertenece a un departamento
     *
     * @return BelongsTo
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'id_departamento', 'id');
    }

    /**
     * Un empleado tiene muchos registros de asistencia
     *
     * @return HasMany
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'id_empleado', 'id');
    }

    /**
     * ===========================================================
     * SCOPES - Filtros reutilizables
     * ===========================================================
     */

    /**
     * Filtrar empleados por departamento
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $idDepartamento
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorDepartamento($query, int $idDepartamento)
    {
        return $query->where('id_departamento', $idDepartamento);
    }

    /**
     * Filtrar empleados activos (con usuario activo)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivos($query)
    {
        return $query->whereHas('usuario', function ($q) {
            $q->where('estado', true);
        });
    }

    /**
     * Buscar empleados por nombre o código
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $termino
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo_empleado', 'LIKE', "%{$termino}%")
              ->orWhereHas('usuario', function ($subq) use ($termino) {
                  $subq->where('nombre', 'LIKE', "%{$termino}%")
                       ->orWhere('apellido', 'LIKE', "%{$termino}%");
              });
        });
    }

    /**
     * ===========================================================
     * ACCESORES (GETTERS)
     * ===========================================================
     */

    /**
     * Obtener el nombre completo del empleado desde el usuario relacionado
     *
     * @return string
     */
    public function getNombreCompletoAttribute(): string
    {
        return $this->usuario ? $this->usuario->nombre . ' ' . $this->usuario->apellido : 'Sin usuario';
    }

    /**
     * Obtener el estado del empleado desde el usuario relacionado
     *
     * @return bool
     */
    public function getEstadoAttribute(): bool
    {
        return $this->usuario ? $this->usuario->estado : false;
    }

    /**
     * Obtener el correo del empleado desde el usuario relacionado
     *
     * @return string|null
     */
    public function getCorreoAttribute(): ?string
    {
        return $this->usuario ? $this->usuario->correo : null;
    }

    /**
     * Formatear el código de empleado con prefijo si es necesario
     *
     * @param string $value
     * @return string
     */
    public function getCodigoEmpleadoAttribute($value): string
    {
        // Si el código ya tiene formato, lo devolvemos, si no, le agregamos EMP-
        if (str_starts_with($value, 'EMP-')) {
            return $value;
        }
        return 'EMP-' . str_pad($value, 3, '0', STR_PAD_LEFT);
    }

    /**
     * ===========================================================
     * MUTADORES (SETTERS)
     * ===========================================================
     */

    /**
     * Asegurar formato del código de empleado
     *
     * @param string $value
     * @return void
     */
    public function setCodigoEmpleadoAttribute($value)
    {
        // Eliminar EMP- si viene y luego formatear
        $codigo = str_replace('EMP-', '', $value);
        $this->attributes['codigo_empleado'] = 'EMP-' . str_pad($codigo, 3, '0', STR_PAD_LEFT);
    }

    /**
     * ===========================================================
     * MÉTODOS PERSONALIZADOS
     * ===========================================================
     */

    /**
     * Verificar si el empleado está dentro (tiene entrada sin salida hoy)
     *
     * @return bool
     */
    public function estaDentro(): bool
    {
        $asistenciaHoy = $this->asistencias()
            ->whereDate('fecha', today())
            ->first();

        return $asistenciaHoy && !$asistenciaHoy->hora_salida;
    }

    /**
     * Obtener la asistencia del día actual
     *
     * @return Asistencia|null
     */
    public function asistenciaHoy(): ?Asistencia
    {
        return $this->asistencias()
            ->whereDate('fecha', today())
            ->first();
    }

    /**
     * Calcular años de antigüedad en la empresa
     *
     * @return int
     */
    public function getAntiguedadAttribute(): int
    {
        return $this->fecha_ingreso->diffInYears(now());
    }

    /**
     * Boot del modelo para eventos
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de crear, generar código de empleado si no viene
        static::creating(function ($empleado) {
            if (empty($empleado->codigo_empleado)) {
                $ultimoEmpleado = static::withTrashed()
                    ->orderBy('id', 'desc')
                    ->first();
                
                $siguienteId = $ultimoEmpleado ? $ultimoEmpleado->id + 1 : 1;
                $empleado->codigo_empleado = 'EMP-' . str_pad($siguienteId, 3, '0', STR_PAD_LEFT);
            }
        });

        // Después de crear, podrías enviar notificaciones, etc.
        static::created(function ($empleado) {
            // Aquí podrías enviar un email de bienvenida, etc.
        });
    }
}