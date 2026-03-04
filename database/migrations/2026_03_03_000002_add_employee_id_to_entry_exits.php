<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add employee_id column
        Schema::table('entry_exits', function (Blueprint $table) {
            $table->uuid('employee_id')->nullable()->after('id');
            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->onDelete('set null');
        });

        // Step 2: Backfill employee_id from existing data

        // 2a: Records with staff_id — find employee via staff DNI → person → employee
        $entriesWithStaff = DB::table('entry_exits')->whereNotNull('staff_id')->get();
        foreach ($entriesWithStaff as $entry) {
            $staff = DB::table('staff')->where('id', $entry->staff_id)->first();
            if ($staff) {
                $person = DB::table('people')->where('dni', $staff->dni)->first();
                if ($person) {
                    $employee = DB::table('employees')->where('person_id', $person->id)->first();
                    if ($employee) {
                        DB::table('entry_exits')
                            ->where('id', $entry->id)
                            ->update(['employee_id' => $employee->id]);
                    }
                }
            }
        }

        // 2b: Records without staff_id — find employee via DNI → person → employee
        $entriesWithoutStaff = DB::table('entry_exits')
            ->whereNull('staff_id')
            ->whereNull('employee_id')
            ->whereNotNull('dni')
            ->get();

        foreach ($entriesWithoutStaff as $entry) {
            $person = DB::table('people')->where('dni', $entry->dni)->first();
            if ($person) {
                $employee = DB::table('employees')->where('person_id', $person->id)->first();
                if ($employee) {
                    DB::table('entry_exits')
                        ->where('id', $entry->id)
                        ->update(['employee_id' => $employee->id]);
                }
            }
        }

        // Step 3: Drop deprecated columns
        Schema::table('entry_exits', function (Blueprint $table) {
            // Try to drop FK if it exists (may not exist in all environments)
            try {
                $table->dropForeign(['staff_id']);
            } catch (\Exception $e) {
                // FK doesn't exist, continue
            }
        });

        Schema::table('entry_exits', function (Blueprint $table) {
            $table->dropColumn(['staff_id', 'dni', 'nombre_personal', 'regimen']);
        });
    }

    public function down(): void
    {
        Schema::table('entry_exits', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->after('id')->constrained('staff')->onDelete('set null');
            $table->string('dni', 8)->after('staff_id');
            $table->string('nombre_personal', 200)->after('dni');
            $table->string('regimen', 50)->nullable()->after('turno');
        });

        Schema::table('entry_exits', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
