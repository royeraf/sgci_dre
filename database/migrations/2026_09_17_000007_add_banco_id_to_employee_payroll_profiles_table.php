<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Reemplaza el campo de texto `banco` por `banco_id` (FK lógica al catálogo).
     * Migra los valores de texto existentes al catálogo `planilla_bancos`.
     */
    public function up(): void
    {
        Schema::table('employee_payroll_profiles', function (Blueprint $table) {
            $table->uuid('banco_id')->nullable()->after('cuspp')->index();
        });

        // Migrar valores de texto existentes al catálogo
        $profiles = DB::table('employee_payroll_profiles')
            ->whereNotNull('banco')
            ->where('banco', '!=', '')
            ->get();

        foreach ($profiles as $profile) {
            $nombre = trim($profile->banco);
            if ($nombre === '') {
                continue;
            }

            $banco = DB::table('planilla_bancos')->where('nombre', $nombre)->first();

            if ($banco) {
                $bancoId = $banco->id;
            } else {
                $bancoId = (string) Str::uuid();
                DB::table('planilla_bancos')->insert([
                    'id' => $bancoId,
                    'nombre' => $nombre,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('employee_payroll_profiles')
                ->where('id', $profile->id)
                ->update(['banco_id' => $bancoId]);
        }

        Schema::table('employee_payroll_profiles', function (Blueprint $table) {
            $table->dropColumn('banco');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payroll_profiles', function (Blueprint $table) {
            $table->string('banco')->nullable()->after('cuspp');
        });

        // Restaurar el texto desde el catálogo
        $profiles = DB::table('employee_payroll_profiles')->whereNotNull('banco_id')->get();
        foreach ($profiles as $profile) {
            $banco = DB::table('planilla_bancos')->where('id', $profile->banco_id)->first();
            if ($banco) {
                DB::table('employee_payroll_profiles')
                    ->where('id', $profile->id)
                    ->update(['banco' => $banco->nombre]);
            }
        }

        Schema::table('employee_payroll_profiles', function (Blueprint $table) {
            $table->dropColumn('banco_id');
        });
    }
};
