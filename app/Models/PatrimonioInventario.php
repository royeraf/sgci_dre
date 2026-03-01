<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrimonioInventario extends Model
{
    protected $table = 'patrimonio_inventarios';

    protected $fillable = [
        'anio',
        'tipo',
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'creado_por',
        'cerrado_por',
        'responsable_saliente_id',
    ];

    protected $casts = [
        'anio'        => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PatrimonioInventarioItem::class, 'inventario_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function responsableSaliente(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsable_saliente_id');
    }
}
