<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Datos de la entidad emisora que aparecen en el encabezado de la boleta de
 * pago: RUC, razón social y dirección.
 *
 * Es un singleton de una sola fila. Se accede siempre mediante
 * {@see self::actual()} para que la boleta nunca falle por tabla vacía.
 */
class DreConfiguracion extends Model
{
    protected $table = 'dre_configuracion';

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_abreviado',
        'direccion',
    ];

    /**
     * Fila única de configuración, creándola con valores vacíos si aún no
     * existe (por ejemplo, en una instalación donde no se corrió el seeder).
     */
    public static function actual(): self
    {
        return static::query()->first() ?? static::query()->create([
            'ruc' => '',
            'razon_social' => '',
            'direccion' => '',
        ]);
    }
}
