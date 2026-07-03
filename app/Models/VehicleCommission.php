<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VehicleCommission extends Model
{
    use HasUuids;

    protected $fillable = [
        'numero',
        'anio',
        'dependencia',
        'solicitante',
        'dia',
        'hora',
        'lugar',
        'referencia',
        'motivo',
        'usuarios',
        'vehicle_id',
        'chofer',
        'funcionario_autoriza',
        'hora_salida',
        'hora_regreso',
        'km_salida',
        'km_retorno',
        'combustible',
        'pnro',
        'estado',
    ];

    protected $casts = [
        'dia' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the next correlative number for a given year (resets each year).
     */
    public static function nextNumero(int $anio): int
    {
        $last = self::where('anio', $anio)->max('numero');

        return ($last ?? 0) + 1;
    }

    public function getTotalKmRecorridoAttribute(): ?int
    {
        if (is_numeric($this->km_salida) && is_numeric($this->km_retorno)) {
            return max(0, (int) $this->km_retorno - (int) $this->km_salida);
        }

        return null;
    }

    /**
     * Get the status display class
     */
    public function getStatusClassAttribute(): string
    {
        return match($this->estado) {
            'EN_COMISION' => 'bg-blue-100 text-blue-800',
            'COMPLETADA' => 'bg-green-100 text-green-800',
            'CANCELADA' => 'bg-gray-100 text-gray-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }
}
