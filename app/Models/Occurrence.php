<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Occurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'hora',
        'turno',
        'vigilante_id',
        'tipo',
        'descripcion',
        'acciones',
        'evidencia_url',
        'estado',
        'created_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i:s',
    ];

    /**
     * Get the vigilante (user) who registered this occurrence.
     */
    public function vigilante()
    {
        return $this->belongsTo(User::class, 'vigilante_id');
    }

    /**
     * Get the user who created this occurrence.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for today's occurrences.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('fecha', now()->toDateString());
    }

    /**
     * Scope for occurrences by type.
     */
    public function scopeByType($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope for occurrences in date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('fecha', [$startDate, $endDate]);
    }

    /**
     * Scope for pending occurrences.
     */
    public function scopePending($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    /**
     * Get shift based on current time.
     */
    public static function getCurrentShift(): string
    {
        $hour = now()->hour;
        
        if ($hour >= 6 && $hour < 14) {
            return 'Mañana';
        } elseif ($hour >= 14 && $hour < 22) {
            return 'Tarde';
        } else {
            return 'Noche';
        }
    }
}
