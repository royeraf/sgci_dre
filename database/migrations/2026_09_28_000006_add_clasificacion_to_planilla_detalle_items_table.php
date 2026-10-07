<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planilla_detalle_items', function (Blueprint $table) {
            $table->uuid('clasificador_id')->nullable()->after('concepto_id');
            $table->string('clasificador_gasto_codigo', 30)->nullable()->after('clasificador_id');
            $table->unsignedSmallInteger('anio_fiscal')->nullable()->after('clasificador_gasto_codigo');
            $table->string('estado_clasificacion', 15)->nullable()->after('anio_fiscal');

            $table->index('clasificador_id', 'pdi_clasificador_index');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_detalle_items', function (Blueprint $table) {
            $table->dropIndex('pdi_clasificador_index');
            $table->dropColumn([
                'clasificador_id',
                'clasificador_gasto_codigo',
                'anio_fiscal',
                'estado_clasificacion',
            ]);
        });
    }
};
