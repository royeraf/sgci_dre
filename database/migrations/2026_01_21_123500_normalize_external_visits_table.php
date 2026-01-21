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
     * 
     * Normaliza la tabla external_visits:
     * 1. Agrega person_id como FK a people
     * 2. Agrega area_id como FK a hr_areas
     * 3. Migra datos existentes
     * 4. Elimina columnas duplicadas (dni, nombres, area)
     */
    public function up(): void
    {
        // Paso 1: Agregar nuevas columnas FK si no existen
        Schema::table('external_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('external_visits', 'person_id')) {
                $table->uuid('person_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('external_visits', 'area_id')) {
                $table->uuid('area_id')->nullable()->after('person_id');
            }
        });

        // Paso 2: Migrar datos existentes
        $visits = DB::table('external_visits')->get();
        
        foreach ($visits as $visit) {
            $personId = null;
            $areaId = null;

            // Buscar o crear persona en people
            if (!empty($visit->dni)) {
                $existingPerson = DB::table('people')->where('dni', $visit->dni)->first();
                
                if ($existingPerson) {
                    $personId = $existingPerson->id;
                } else {
                    // Crear nueva persona externa
                    $personId = (string) Str::uuid();
                    
                    // Separar nombres (asumiendo formato "Nombres Apellidos" o solo nombre)
                    $nombreCompleto = $visit->nombres ?? '';
                    $partes = explode(' ', trim($nombreCompleto), 2);
                    $nombres = $partes[0] ?? $nombreCompleto;
                    $apellidos = $partes[1] ?? '';

                    DB::table('people')->insert([
                        'id' => $personId,
                        'dni' => $visit->dni,
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'tipo' => 'EXTERNO',
                        'is_active' => true,
                        'created_at' => $visit->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Buscar área por nombre si existe
            if (!empty($visit->area)) {
                $area = DB::table('hr_areas')->where('nombre', 'LIKE', '%' . $visit->area . '%')->first();
                if ($area) {
                    $areaId = $area->id;
                }
            }

            // Actualizar registro con las nuevas FK
            DB::table('external_visits')
                ->where('id', $visit->id)
                ->update([
                    'person_id' => $personId,
                    'area_id' => $areaId,
                ]);
        }

        // Paso 3: Agregar FK constraints si no existen
        Schema::table('external_visits', function (Blueprint $table) {
            // Verificar si la FK ya existe antes de crearla
            $foreignKeys = $this->getTableForeignKeys('external_visits');
            
            if (!in_array('external_visits_person_id_foreign', $foreignKeys)) {
                $table->foreign('person_id')
                      ->references('id')
                      ->on('people')
                      ->onDelete('cascade');
            }
            
            if (!in_array('external_visits_area_id_foreign', $foreignKeys)) {
                $table->foreign('area_id')
                      ->references('id')
                      ->on('hr_areas')
                      ->onDelete('set null');
            }
        });

        // Paso 4: Eliminar columnas duplicadas si existen
        Schema::table('external_visits', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('external_visits', 'dni')) {
                $columnsToDrop[] = 'dni';
            }
            if (Schema::hasColumn('external_visits', 'nombres')) {
                $columnsToDrop[] = 'nombres';
            }
            if (Schema::hasColumn('external_visits', 'area')) {
                $columnsToDrop[] = 'area';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Obtener lista de foreign keys de una tabla
     */
    private function getTableForeignKeys(string $table): array
    {
        $foreignKeys = [];
        $results = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table]);
        
        foreach ($results as $result) {
            $foreignKeys[] = $result->CONSTRAINT_NAME;
        }
        
        return $foreignKeys;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar columnas originales
        Schema::table('external_visits', function (Blueprint $table) {
            // Eliminar FK si existen
            $foreignKeys = $this->getTableForeignKeys('external_visits');
            
            if (in_array('external_visits_person_id_foreign', $foreignKeys)) {
                $table->dropForeign(['person_id']);
            }
            if (in_array('external_visits_area_id_foreign', $foreignKeys)) {
                $table->dropForeign(['area_id']);
            }
        });

        Schema::table('external_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('external_visits', 'dni')) {
                $table->string('dni', 8)->after('id');
            }
            if (!Schema::hasColumn('external_visits', 'nombres')) {
                $table->string('nombres', 200)->after('dni');
            }
            if (!Schema::hasColumn('external_visits', 'area')) {
                $table->string('area', 100)->nullable()->after('motivo');
            }
        });

        // Restaurar datos desde people
        $visits = DB::table('external_visits')->get();
        foreach ($visits as $visit) {
            if ($visit->person_id) {
                $person = DB::table('people')->where('id', $visit->person_id)->first();
                if ($person) {
                    DB::table('external_visits')
                        ->where('id', $visit->id)
                        ->update([
                            'dni' => $person->dni,
                            'nombres' => $person->nombres . ' ' . $person->apellidos,
                        ]);
                }
            }
            if ($visit->area_id) {
                $area = DB::table('hr_areas')->where('id', $visit->area_id)->first();
                if ($area) {
                    DB::table('external_visits')
                        ->where('id', $visit->id)
                        ->update(['area' => $area->nombre]);
                }
            }
        }

        Schema::table('external_visits', function (Blueprint $table) {
            if (Schema::hasColumn('external_visits', 'person_id')) {
                $table->dropColumn('person_id');
            }
            if (Schema::hasColumn('external_visits', 'area_id')) {
                $table->dropColumn('area_id');
            }
        });
    }
};
