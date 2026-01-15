<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntryExit extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'dni',
        'nombre_personal',
        'hora_salida',
        'hora_retorno',
        'motivo',
        'tipo_motivo',
        'papeleta',
        'turno',
        'regimen',
        'fecha',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_salida' => 'datetime:H:i:s',
        'hora_retorno' => 'datetime:H:i:s',
    ];

    /**
     * Get the staff member for this entry/exit.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the user who registered this entry/exit.
     */
    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registrado_por');
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
