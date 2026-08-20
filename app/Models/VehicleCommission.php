<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VehicleCommission extends Model
{
    use HasUuids;

    protected $fillable = [
        'numero',
        'anio',
        'solicitante_employee_id',
        'dia',
        'hora',
        'lugar',
        'referencia',
        'motivo',
        'usuarios',
        'vehicle_id',
        'conductor_employee_id',
        'autorizado_por',
        'fecha_autorizacion',
        'comentario_autorizacion',
        'fecha_confirmacion_conductor',
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
        'fecha_autorizacion' => 'datetime',
        'fecha_confirmacion_conductor' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function solicitanteEmployee()
    {
        return $this->belongsTo(Employee::class, 'solicitante_employee_id');
    }

    public function getSolicitanteNombreAttribute(): ?string
    {
        return $this->solicitanteEmployee?->person?->nombre_full;
    }

    public function conductorEmployee()
    {
        return $this->belongsTo(Employee::class, 'conductor_employee_id');
    }

    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'vehicle_commission_passengers', 'vehicle_commission_id', 'employee_id');
    }

    public function getConductorNombreAttribute(): ?string
    {
        return $this->conductorEmployee?->person?->nombre_full;
    }

    public function getConductorLicenciaAttribute(): ?string
    {
        $lic = $this->conductorEmployee?->driverLicense;

        return $lic ? "{$lic->numero} ({$lic->categoria})" : null;
    }

    public function autorizadorEmployee()
    {
        return $this->belongsTo(Employee::class, 'autorizado_por');
    }

    public function getAutorizadoPorNombreAttribute(): ?string
    {
        return $this->autorizadorEmployee?->person?->nombre_full;
    }

    /**
     * Estado en el que se acepta la solicitud pendiente de autorización.
     */
    public function necesitaAutorizacion(): bool
    {
        return $this->estado === 'PENDIENTE';
    }

    /**
     * Estado en el que el conductor asignado debe confirmar la salida.
     */
    public function necesitaConfirmacionConductor(): bool
    {
        return $this->estado === 'AUTORIZADA';
    }

    public function estaAutorizada(): bool
    {
        return !is_null($this->autorizado_por);
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
            'AUTORIZADA' => 'bg-indigo-100 text-indigo-800',
            'CONFIRMADA' => 'bg-cyan-100 text-cyan-800',
            'EN_COMISION' => 'bg-blue-100 text-blue-800',
            'COMPLETADA' => 'bg-green-100 text-green-800',
            'RECHAZADA' => 'bg-red-100 text-red-800',
            'CANCELADA' => 'bg-gray-100 text-gray-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }
}
