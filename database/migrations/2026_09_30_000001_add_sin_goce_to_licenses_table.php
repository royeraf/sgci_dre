<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Licencia sin goce: no se paga la remuneración de esos días y el
     * generador los descuenta del periodo (si cubren el mes completo, el
     * empleado se excluye de la planilla, como en el Excel).
     */
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->boolean('sin_goce')->default(false)->after('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('sin_goce');
        });
    }
};
