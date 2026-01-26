<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar la columna nullable primero
        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('contract_type_id')->nullable()->after('position_id');
            $table->foreign('contract_type_id')->references('id')->on('hr_contract_types');
        });

        // 2. Poblar tipos de contrato y migrar datos
        // Obtener tipos únicos actuales
        $existingTypes = DB::table('employees')->whereNotNull('tipo_contrato')->distinct()->pluck('tipo_contrato');
        
        // Agregar tipos por defecto si no existen
        $defaultTypes = collect(['Nombrado', 'CAS', 'Locador', 'Practicante', '276'])->merge($existingTypes)->unique();

        foreach ($defaultTypes as $typeName) {
            if (empty($typeName)) continue;

            $id = Str::uuid()->toString();
            
            // Insertar o ignorar si ya existe (por nombre unique)
            // Como acabamos de crear la tabla, insertamos directamente
            // Verificar si ya existe para evitar duplicados en migraciones re-run
            $exists = DB::table('hr_contract_types')->where('nombre', $typeName)->first();
            
            if (!$exists) {
                DB::table('hr_contract_types')->insert([
                    'id' => $id,
                    'nombre' => $typeName,
                    'descripcion' => 'Generado o importado automáticamente',
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $contractTypeId = $id;
            } else {
                $contractTypeId = $exists->id;
            }

            // Actualizar empleados
            DB::table('employees')
                ->where('tipo_contrato', $typeName)
                ->update(['contract_type_id' => $contractTypeId]);
        }

        // 3. Eliminar columna antigua (opcional, por seguridad la dejaremos un rato o la renombraremos, pero el usuario pidió que sea una tabla nueva referencia)
        // Vamos a eliminarla para forzar el uso de la relación
        Schema::table('employees', function (Blueprint $table) {
             $table->dropColumn('tipo_contrato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('tipo_contrato')->nullable();
        });

        // Restaurar datos (aproximado)
        $results = DB::table('employees')
            ->join('hr_contract_types', 'employees.contract_type_id', '=', 'hr_contract_types.id')
            ->select('employees.id', 'hr_contract_types.nombre')
            ->get();

        foreach ($results as $row) {
            DB::table('employees')->where('id', $row->id)->update(['tipo_contrato' => $row->nombre]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['contract_type_id']);
            $table->dropColumn('contract_type_id');
        });
    }
};
