<?php
/**
 * Modelo Asistencia
 * 
 * Archivo: app/Models/Asistencia.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Asistencia extends Model
{
    use HasFactory;

    /**
     * Tabla asociada
     *
     * @var string
     */
    protected $table = 'asistencias';

    /**
     * Atributos asignables
     *
     * @var array<string>
     */
    protected $fillable = [
        'id_empleado',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'total_horas',
        'observacion'
    ];

    /**
     * Atributos a castear
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha' => 'date',
        'hora_entrada' => 'datetime:H:i:s',
        'hora_salida' => 'datetime:H:i:s',
        'total_horas' => 'decimal:2'
    ];

    /**
     * ===========================================================
     * RELACIONES
     * ===========================================================
     */

    /**
     * Una asistencia pertenece a un empleado
     *
     * @return BelongsTo
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id');
    }

    /**
     * ===========================================================
     * SCOPES
     * ===========================================================
     */

    /**
     * Filtrar por rango de fechas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEntreFechas($query, string $fechaInicio, string $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     * Asistencias del día de hoy
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHoy($query)
    {
        return $query->whereDate('fecha', today());
    }

    /**
     * ===========================================================
     * ACCESORES
     * ===========================================================
     */

    /**
     * Obtener hora de entrada formateada
     *
     * @return string|null
     */
    public function getHoraEntradaFormatoAttribute(): ?string
    {
        return $this->hora_entrada ? Carbon::parse($this->hora_entrada)->format('h:i A') : null;
    }

    /**
     * Obtener hora de salida formateada
     *
     * @return string|null
     */
    public function getHoraSalidaFormatoAttribute(): ?string
    {
        return $this->hora_salida ? Carbon::parse($this->hora_salida)->format('h:i A') : null;
    }

    /**
     * Calcular total de horas automáticamente si no existe
     *
     * @return float|null
     */
    public function getTotalHorasCalculadoAttribute(): ?float
    {
        if ($this->hora_entrada && $this->hora_salida) {
            $entrada = Carbon::parse($this->hora_entrada);
            $salida = Carbon::parse($this->hora_salida);
            return round($salida->diffInMinutes($entrada) / 60, 2);
        }
        return null;
    }
}