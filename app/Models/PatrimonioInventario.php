<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrimonioInventario extends Model
{
    protected $table = 'patrimonio_inventarios';

    protected $fillable = [
        'anio',
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'creado_por',
        'cerrado_por',
    ];

    protected $casts = [
        'anio'        => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }
}
