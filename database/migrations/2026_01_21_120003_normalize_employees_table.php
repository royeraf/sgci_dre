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
     * Normaliza la tabla employees:
     * 1. Migra los datos personales a la tabla 'people'
     * 2. Agrega columna person_id como FK
     * 3. Agrega columnas para FK de area, position y office
     * 4. Elimina columnas duplicadas (dni, nombres, apellidos, etc.)
     */
    public function up(): void
    {
        // Paso 1: Agregar nuevas columnas de FK
        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('person_id')->nullable()->after('id');
            $table->uuid('area_id_new')->nullable()->after('area');
            $table->uuid('position_id')->nullable()->after('cargo');
            $table->uuid('office_id')->nullable()->after('position_id');
        });

        // Paso 2: Migrar datos existentes de employees a people
        $employees = DB::table('employees')->get();
        
        foreach ($employees as $employee) {
            // Verificar si ya existe la persona en people
            $existingPerson = DB::table('people')->where('dni', $employee->dni)->first();
            
            if ($existingPerson) {
                $personId = $existingPerson->id;
            } else {
                // Crear nueva persona en la tabla people
                $personId = (string) Str::uuid();
                DB::table('people')->insert([
                    'id' => $personId,
                    'dni' => $employee->dni,
                    'nombres' => $employee->nombres,
                    'apellidos' => $employee->apellidos,
                    'fecha_nacimiento' => $employee->fecha_nacimiento ?? null,
                    'genero' => $employee->genero ?? null,
                    'direccion' => $employee->direccion ?? null,
                    'telefono' => $employee->telefono ?? null,
                    'email' => $employee->correo ?? null,
                    'tipo' => 'INTERNO',
                    'is_active' => $employee->estado === 'ACTIVO',
                    'created_at' => $employee->created_at,
                    'updated_at' => now(),
                ]);
            }

            // Buscar area_id si existe el área por nombre
            $areaId = null;
            if ($employee->area) {
                $area = DB::table('hr_areas')->where('nombre', $employee->area)->first();
                if ($area) {
                    $areaId = $area->id;
                }
            }

            // Buscar position_id si existe el cargo por nombre
            $positionId = null;
            if ($employee->cargo) {
                $position = DB::table('hr_positions')->where('nombre', $employee->cargo)->first();
                if ($position) {
                    $positionId = $position->id;
                }
            }

            // Actualizar employee con las nuevas FK
            DB::table('employees')
                ->where('id', $employee->id)
                ->update([
                    'person_id' => $personId,
                    'area_id_new' => $areaId,
                    'position_id' => $positionId,
                ]);
        }

        // Paso 3: Hacer person_id requerido y agregar FK
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('person_id')
                  ->references('id')
                  ->on('people')
                  ->onDelete('cascade');

            $table->foreign('area_id_new')
                  ->references('id')
                  ->on('hr_areas')
                  ->onDelete('set null');

            $table->foreign('position_id')
                  ->references('id')
                  ->on('hr_positions')
                  ->onDelete('set null');

            $table->foreign('office_id')
                  ->references('id')
                  ->on('hr_offices')
                  ->onDelete('set null');
        });

        // Paso 4: Eliminar columnas duplicadas
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'dni',
                'nombres',
                'apellidos',
                'fecha_nacimiento',
                'genero',
                'direccion',
                'telefono',
                'correo',
                'cargo',
                'area',
            ]);
        });

        // Paso 5: Renombrar area_id_new a area_id
        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('area_id_new', 'area_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar columnas originales
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['area_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['office_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('area_id', 'area_id_new');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('dni', 8)->after('id');
            $table->string('nombres')->after('dni');
            $table->string('apellidos')->after('nombres');
            $table->date('fecha_nacimiento')->nullable()->after('apellidos');
            $table->enum('genero', ['Masculino', 'Femenino'])->nullable()->after('fecha_nacimiento');
            $table->string('direccion')->nullable()->after('genero');
            $table->string('telefono', 20)->nullable()->after('direccion');
            $table->string('correo')->nullable()->after('telefono');
            $table->string('cargo')->nullable()->after('correo');
            $table->string('area')->nullable()->after('cargo');
        });

        // Restaurar datos desde people
        $employees = DB::table('employees')->get();
        foreach ($employees as $employee) {
            if ($employee->person_id) {
                $person = DB::table('people')->where('id', $employee->person_id)->first();
                if ($person) {
                    DB::table('employees')
                        ->where('id', $employee->id)
                        ->update([
                            'dni' => $person->dni,
                            'nombres' => $person->nombres,
                            'apellidos' => $person->apellidos,
                            'fecha_nacimiento' => $person->fecha_nacimiento,
                            'genero' => $person->genero,
                            'direccion' => $person->direccion,
                            'telefono' => $person->telefono,
                            'correo' => $person->email,
                        ]);
                }
            }
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['person_id', 'area_id_new', 'position_id', 'office_id']);
        });
    }
};
