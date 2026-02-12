<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SbnCatalogItem extends Model
{
    protected $table = 'sbn_catalog_items';

    protected $fillable = [
        'codigo',
        'denominacion',
        'grupo_generico',
        'clase',
    ];
}
