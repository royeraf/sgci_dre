<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntryExit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'entry_exit_reason_id',
        'hora_salida',
        'hora_retorno',
        'motivo',
        'papeleta',
        'turno',
        'fecha',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_salida' => 'datetime:H:i:s',
        'hora_retorno' => 'datetime:H:i:s',
    ];

    /**
     * Relación con el empleado.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reason()
    {
        return $this->belongsTo(EntryExitReason::class, 'entry_exit_reason_id');
    }

    /**
     * Get the user who registered this entry/exit.
     */
    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // ===== ACCESSORS (datos derivados del empleado) =====

    public function getDniAttribute(): ?string
    {
        return $this->employee?->dni;
    }

    public function getNombrePersonalAttribute(): ?string
    {
        return $this->employee?->full_name;
    }

    public function getRegimenAttribute(): ?string
    {
        return $this->employee?->tipo_contrato;
    }

    public function getTipoMotivoAttribute(): ?string
    {
        return $this->reason?->tipo;
    }

    /**
     * Check if return is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return is_null($this->hora_retorno);
    }

    /**
     * Scope for pending returns.
     */
    public function scopePending($query)
    {
        return $query->whereNull('hora_retorno');
    }

    /**
     * Scope for completed entries.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('hora_retorno');
    }

    /**
     * Scope for today's entries.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('fecha', now()->toDateString());
    }

    /**
     * Generate next papeleta number.
     */
    public static function generatePapeletaNumber(): string
    {
        $last = self::orderBy('id', 'desc')->first();

        if (!$last || !is_numeric($last->papeleta)) {
            return '000001';
        }

        return str_pad((int)$last->papeleta + 1, 6, '0', STR_PAD_LEFT);
    }
}
