<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla de inventario de vehículos
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo')->default('Automóvil'); // Automóvil, Camioneta, Minivan, etc.
            $table->string('marca');
            $table->string('modelo');
            $table->string('placa')->unique();
            $table->string('anio')->nullable();
            $table->string('motor')->nullable();
            $table->string('chasis')->nullable();
            $table->string('cilindros')->nullable();
            $table->string('asientos')->nullable();
            $table->string('color')->nullable();
            $table->string('estado')->default('Operativo'); // Operativo, En Mantenimiento, Inoperativo
            $table->string('kilometraje')->nullable();
            $table->string('combustible')->default('Gasolina'); // Gasolina, Diesel, GLP
            $table->date('fecha_soat')->nullable();
            $table->date('fecha_revision')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // Tabla de comisiones/solicitudes de vehículo
        Schema::create('vehicle_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('dependencia');
            $table->date('dia');
            $table->time('hora');
            $table->string('lugar');
            $table->text('motivo')->nullable();
            $table->string('usuarios')->nullable();
            $table->foreignUuid('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->string('chofer');
            $table->time('hora_salida')->nullable();
            $table->time('hora_regreso')->nullable();
            $table->string('estado')->default('PENDIENTE'); // PENDIENTE, EN_COMISION, COMPLETADA, CANCELADA
            $table->timestamps();
        });

        // Tabla de gastos de mantenimiento
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->date('fecha');
            $table->string('factura')->nullable();
            $table->string('kilometraje')->nullable();
            $table->string('orden_sc')->nullable();
            $table->string('proveedor')->nullable();
            $table->text('detalle');
            $table->decimal('costo', 10, 2);
            $table->string('vigilante')->nullable();
            $table->string('responsable')->nullable();
            $table->timestamps();
        });

        // Tabla de actas de entrega y recepción
        Schema::create('vehicle_handovers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('fecha');
            $table->string('entidad')->nullable();
            $table->string('denominacion')->nullable();
            $table->string('placa');
            $table->string('color')->nullable();
            $table->string('kilometraje');
            $table->string('carroceria')->nullable();
            $table->string('n_motor')->nullable();
            $table->json('sistemas')->nullable(); // JSON con el estado de cada sistema
            $table->string('abastecimiento')->nullable();
            $table->string('recepciona');
            $table->string('entrega')->nullable();
            $table->timestamps();
        });

        // Tabla de requerimientos de servicio
        Schema::create('vehicle_service_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('conductor');
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('estado_vehiculo')->nullable();
            $table->string('estado_motor')->nullable();
            $table->string('encendido_electrico')->nullable();
            $table->string('transmision')->nullable();
            $table->string('pintura_carroceria')->nullable();
            $table->string('llantas')->nullable();
            $table->string('herramientas')->nullable();
            $table->string('implementos_aseo')->nullable();
            $table->text('comisiones_realizadas')->nullable();
            $table->text('motivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_requirements');
        Schema::dropIfExists('vehicle_handovers');
        Schema::dropIfExists('vehicle_maintenances');
        Schema::dropIfExists('vehicle_commissions');
        Schema::dropIfExists('vehicles');
    }
};
