<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaComisionAfp extends Model
{
    protected $table = 'planilla_comisiones_afp';

    protected $fillable = [
        'mes',
        'regimen_pensionario_id',
        'comision_flujo',
        'comision_saldo',
    ];

    protected $casts = [
        'mes' => 'date',
        'comision_flujo' => 'decimal:4',
        'comision_saldo' => 'decimal:4',
    ];

    public function regimenPensionario(): BelongsTo
    {
        return $this->belongsTo(PlanillaRegimenPensionario::class, 'regimen_pensionario_id');
    }

    public static function delMes(Carbon $mes): ?self
    {
        $clave = $mes->copy()->startOfMonth();

        return static::where('mes', $clave)->first();
    }
}
