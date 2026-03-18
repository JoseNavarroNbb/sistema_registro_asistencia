<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    use HasFactory;

    /**
     * Tabla asociada
     *
     * @var string
     */
    protected $table = 'departamentos';

    /**
     * Atributos asignables
     *
     * @var array<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    /**
     * ===========================================================
     * RELACIONES
     * ===========================================================
     */

    /**
     * Un departamento tiene muchos empleados
     *
     * @return HasMany
     */
    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'id_departamento', 'id');
    }

    /**
     * ===========================================================
     * SCOPES
     * ===========================================================
     */

    /**
     * Departamentos con empleados activos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConEmpleadosActivos($query)
    {
        return $query->whereHas('empleados', function ($q) {
            $q->activos();
        });
    }

    /**
     * Obtener total de empleados en el departamento
     *
     * @return int
     */
    public function getTotalEmpleadosAttribute(): int
    {
        return $this->empleados()->count();
    }
}