<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->time('entrada_manana');          // hora referencia tardanza mañana
            $table->time('salida_manana');           // fin jornada mañana
            $table->time('entrada_tarde')->nullable(); // inicio tarde (null = turno continuo)
            $table->time('salida_tarde')->nullable();  // fin jornada tarde / fin turno
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Horario por defecto DRE
        DB::table('horarios')->insert([
            'nombre'          => 'Horario General DRE',
            'entrada_manana'  => '08:00:00',
            'salida_manana'   => '13:00:00',
            'entrada_tarde'   => '15:00:00',
            'salida_tarde'    => '18:00:00',
            'descripcion'     => 'Horario oficial DRE Huánuco: 8am-1pm y 3pm-6pm',
            'activo'          => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
