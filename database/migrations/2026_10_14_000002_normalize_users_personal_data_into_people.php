<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Columnas personales duplicadas en `users` que se eliminan.
     */
    private const COLUMNAS_PERSONALES = ['dni', 'name', 'apellidos', 'email', 'telefono', 'titulo'];

    /**
     * Normaliza la tabla `users`: los datos personales (DNI, nombres, apellidos,
     * correo, celular y título) viven únicamente en `people`. Antes de eliminar
     * las columnas, cada usuario sin persona se vincula/crea en `people`.
     */
    public function up(): void
    {
        foreach (DB::table('users')->get() as $user) {
            $personId = $user->person_id;

            // Si ya apunta a una persona válida, no hay nada que migrar.
            if ($personId && DB::table('people')->where('id', $personId)->exists()) {
                continue;
            }

            $dni = trim((string) ($user->dni ?? ''));
            $persona = $dni !== '' ? DB::table('people')->where('dni', $dni)->first() : null;

            if ($persona) {
                $personId = $persona->id;
            } else {
                if (!preg_match('/^[0-9]{8}$/', $dni)) {
                    $dni = $this->dniDisponible($dni !== '' ? $dni : (string) $user->username);
                }

                $personId = (string) Str::uuid();

                DB::table('people')->insert([
                    'id'         => $personId,
                    'dni'        => $dni,
                    'nombres'    => $user->name ?: ($user->username ?: 'Usuario'),
                    'apellidos'  => $user->apellidos ?: '',
                    'titulo'     => $user->titulo ?? null,
                    'telefono'   => $user->telefono ?? null,
                    'email'      => $user->email ?? null,
                    'tipo'       => 'INTERNO',
                    'is_active'  => (bool) ($user->is_active ?? true),
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('users')->where('id', $user->id)->update(['person_id' => $personId]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNAS_PERSONALES);
        });
    }

    /**
     * Reversible: vuelve a crear las columnas y las repuebla desde `people`.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dni', 8)->nullable();
            $table->string('name', 191)->nullable();
            $table->string('apellidos', 100)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('titulo', 20)->nullable();
        });

        foreach (DB::table('users')->whereNotNull('person_id')->get() as $user) {
            $persona = DB::table('people')->where('id', $user->person_id)->first();
            if (!$persona) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update([
                'dni'       => $persona->dni,
                'name'      => $persona->nombres,
                'apellidos' => $persona->apellidos,
                'email'     => $persona->email,
                'telefono'  => $persona->telefono,
                'titulo'    => $persona->titulo,
            ]);
        }
    }

    /**
     * Genera un DNI de 8 dígitos libre a partir de una cadena base.
     */
    private function dniDisponible(string $base): string
    {
        $digits = preg_replace('/\D/', '', $base);
        $candidate = str_pad(substr($digits, 0, 8), 8, '0', STR_PAD_LEFT);

        while (DB::table('people')->where('dni', $candidate)->exists()) {
            $candidate = (string) random_int(10000000, 99999999);
        }

        return $candidate;
    }
};
