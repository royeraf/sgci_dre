<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PapeletaRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'numero_papeleta',
        'employee_id',
        'entry_exit_reason_id',
        'motivo',
        'fecha_salida',
        'hora_salida_estimada',
        'hora_retorno_estimada',
        'turno',
        'estado',
        'aprobado_por',
        'fecha_aprobacion',
        'comentario_aprobacion',
        'aprobado_por_jefe',
        'fecha_aprobacion_jefe',
        'aprobado_por_rrhh',
        'fecha_aprobacion_rrhh',
    ];

    protected $casts = [
        'fecha_salida' => 'date',
        'fecha_aprobacion' => 'datetime',
        'fecha_aprobacion_jefe' => 'datetime',
        'fecha_aprobacion_rrhh' => 'datetime',
    ];

    protected $appends = [
        'solicitante_nombre',
        'solicitante_dni',
        'tipo_motivo',
    ];

    // Relationships

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'aprobado_por');
    }

    public function aprobadorJefe(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'aprobado_por_jefe');
    }

    public function aprobadorRrhh(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'aprobado_por_rrhh');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(EntryExitReason::class, 'entry_exit_reason_id');
    }

    // Accessors

    public function getSolicitanteNombreAttribute(): ?string
    {
        return $this->employee?->full_name;
    }

    public function getSolicitanteDniAttribute(): ?string
    {
        return $this->employee?->dni;
    }

    public function getTipoMotivoAttribute(): ?string
    {
        return $this->reason?->tipo;
    }

    // Scopes

    public function scopePendiente($query)
    {
        return $query->where('estado', 'PENDIENTE');
    }

    public function scopePendienteRrhh($query)
    {
        return $query->where('estado', 'PENDIENTE_RRHH');
    }

    public function scopeAprobado($query)
    {
        return $query->where('estado', 'APROBADO');
    }

    public function scopeDesaprobado($query)
    {
        return $query->where('estado', 'DESAPROBADO');
    }

    public function scopeForDirection($query, $directionId)
    {
        return $query->whereHas('employee', function ($q) use ($directionId) {
            $q->where('direction_id', $directionId);
        });
    }

    public function scopeForOffice($query, $officeId)
    {
        return $query->whereHas('employee', function ($q) use ($officeId) {
            $q->where('office_id', $officeId);
        });
    }

    /**
     * Generate next papeleta number (sequential 6-digit).
     */
    public static function generateNumeroPapeleta(): string
    {
        $last = self::orderBy('created_at', 'desc')->first();

        if (!$last || !is_numeric($last->numero_papeleta)) {
            return '000001';
        }

        return str_pad((int)$last->numero_papeleta + 1, 6, '0', STR_PAD_LEFT);
    }

    public function necesitaAprobacionJefe(): bool
    {
        return in_array($this->estado, ['PENDIENTE']);
    }

    public function necesitaAprobacionRrhh(): bool
    {
        return in_array($this->estado, ['PENDIENTE_RRHH']);
    }

    public function estaAprobadaPorJefe(): bool
    {
        return !is_null($this->aprobado_por_jefe);
    }

    public function estaAprobadaPorRrhh(): bool
    {
        return !is_null($this->aprobado_por_rrhh);
    }
}
