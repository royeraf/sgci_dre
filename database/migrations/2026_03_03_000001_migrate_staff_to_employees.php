<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $staffMembers = DB::table('staff')->get();

        foreach ($staffMembers as $staff) {
            // Check if Person already exists with this DNI
            $person = DB::table('people')->where('dni', $staff->dni)->first();

            if (!$person) {
                $personId = Str::uuid()->toString();
                DB::table('people')->insert([
                    'id' => $personId,
                    'dni' => $staff->dni,
                    'nombres' => $staff->nombres,
                    'apellidos' => $staff->apellidos,
                    'telefono' => $staff->telefono,
                    'email' => $staff->email,
                    'tipo' => 'INTERNO',
                    'is_active' => $staff->is_active,
                    'created_at' => $staff->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            } else {
                $personId = $person->id;
            }

            // Check if Employee already exists for this person
            $employee = DB::table('employees')->where('person_id', $personId)->first();

            if (!$employee) {
                // Find or create Direction
                $directionId = null;
                if ($staff->area) {
                    $direction = DB::table('hr_directions')->where('nombre', $staff->area)->first();
                    if (!$direction) {
                        $directionId = Str::uuid()->toString();
                        DB::table('hr_directions')->insert([
                            'id' => $directionId,
                            'nombre' => $staff->area,
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $directionId = $direction->id;
                    }
                }

                // Find or create Position
                $positionId = null;
                if ($staff->cargo) {
                    $position = DB::table('hr_positions')->where('nombre', $staff->cargo)->first();
                    if (!$position) {
                        $positionId = Str::uuid()->toString();
                        DB::table('hr_positions')->insert([
                            'id' => $positionId,
                            'nombre' => $staff->cargo,
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $positionId = $position->id;
                    }
                }

                // Find or create ContractType
                $contractTypeId = null;
                if ($staff->regimen) {
                    $contractType = DB::table('hr_contract_types')->where('nombre', $staff->regimen)->first();
                    if (!$contractType) {
                        $contractTypeId = Str::uuid()->toString();
                        DB::table('hr_contract_types')->insert([
                            'id' => $contractTypeId,
                            'nombre' => $staff->regimen,
                            'descripcion' => $staff->regimen,
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $contractTypeId = $contractType->id;
                    }
                }

                DB::table('employees')->insert([
                    'id' => Str::uuid()->toString(),
                    'person_id' => $personId,
                    'direction_id' => $directionId,
                    'position_id' => $positionId,
                    'office_id' => null,
                    'contract_type_id' => $contractTypeId,
                    'fecha_ingreso' => null,
                    'estado' => $staff->is_active ? 'ACTIVO' : 'INACTIVO',
                    'observaciones' => 'Migrado desde tabla staff',
                    'licencias_totales' => 0,
                    'licencias_usadas' => 0,
                    'created_at' => $staff->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove employees migrated from staff
        DB::table('employees')->where('observaciones', 'Migrado desde tabla staff')->delete();
    }
};
