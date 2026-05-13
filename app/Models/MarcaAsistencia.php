<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarcaAsistencia extends Model
{
    use HasFactory;

    protected $table = 'marcas_asistencia';

    protected $fillable = [
        'employee_id',
        'fecha',
        'entrada',
        'salida_mediodia',
        'retorno_mediodia',
        'salida',
        'entrada_3',
        'salida_3',
        'entrada_4',
        'salida_4',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];

    // ===== RELACIONES =====

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // ===== SCOPES =====

    public function scopeDelMes($query, int $year, int $month)
    {
        return $query->whereYear('fecha', $year)->whereMonth('fecha', $month);
    }

    public function scopeDelRango($query, string $desde, string $hasta)
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }

    // ===== HELPERS =====

    /**
     * Cuenta cuántas marcas están registradas en este día.
     */
    public function getTotalMarcasAttribute(): int
    {
        return collect([
            $this->entrada, $this->salida_mediodia,
            $this->retorno_mediodia, $this->salida,
            $this->entrada_3, $this->salida_3,
            $this->entrada_4, $this->salida_4,
        ])->filter()->count();
    }

    /**
     * Devuelve los pares entrada/salida presentes en el día.
     * Formato: [['entrada'=>'07:30','salida'=>'13:00'], ...]
     */
    public function getPairesAttribute(): array
    {
        $pares = [];
        $pairs = [
            [$this->entrada,   $this->salida_mediodia],
            [$this->retorno_mediodia, $this->salida],
            [$this->entrada_3, $this->salida_3],
            [$this->entrada_4, $this->salida_4],
        ];
        foreach ($pairs as [$e, $s]) {
            if ($e || $s) $pares[] = ['entrada' => $e, 'salida' => $s];
        }
        return $pares;
    }
}
