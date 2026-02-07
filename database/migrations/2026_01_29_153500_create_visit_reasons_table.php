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
        // 1. Create visit_reasons table
        Schema::create('visit_reasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add visit_reason_id to external_visits
        Schema::table('external_visits', function (Blueprint $table) {
            $table->uuid('visit_reason_id')->nullable()->after('person_id');
            
            $table->foreign('visit_reason_id')
                  ->references('id')
                  ->on('visit_reasons')
                  ->onDelete('set null');
        });

        // 3. Insert some default reasons
        $reasons = [
            ['id' => (string) Str::uuid(), 'nombre' => 'Coordinacion de trabajo', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'nombre' => 'Seguimiento de documento', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'nombre' => 'Fedatear', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'nombre' => 'Consulta presonal', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('visit_reasons')->insert($reasons);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_visits', function (Blueprint $table) {
            $table->dropForeign(['visit_reason_id']);
            $table->dropColumn('visit_reason_id');
        });

        Schema::dropIfExists('visit_reasons');
    }
};
