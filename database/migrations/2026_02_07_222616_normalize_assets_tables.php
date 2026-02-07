<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración normaliza las tablas de patrimonio:
     * 1. assets: elimina campos redundantes, agrega FKs a catálogos
     * 2. asset_responsibles: vincula a tablas de RRHH
     * 3. asset_movements: elimina campos redundantes, agrega FK a estados
     */
    public function up(): void
    {
        // =============================================
        // 1. MODIFICAR TABLA ASSETS
        // =============================================
        Schema::table('assets', function (Blueprint $table) {
            // Agregar nuevas FKs para catálogos
            $table->foreignId('marca_id')->nullable()->after('categoria_id')
                  ->constrained('asset_brands')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->after('marca_id')
                  ->constrained('asset_colors')->nullOnDelete();
            $table->foreignId('origen_id')->nullable()->after('color_id')
                  ->constrained('asset_origins')->nullOnDelete();
            
            // Renombrar detalle_bien a denominacion
            $table->renameColumn('detalle_bien', 'denominacion');
        });
        
        // Eliminar columnas que ahora vienen de movements
        Schema::table('assets', function (Blueprint $table) {
            // Eliminar FKs primero
            $table->dropForeign(['responsable_id']);
            
            // Eliminar columnas
            $table->dropColumn([
                'responsable_id',
                'estado',
                'marca',
                'color',
                'tipo_origen',
                'tipo_modalidad',
                'fecha_asignacion',
                'oficina',
                'fuente',
                'tipo_registro',
                'codigo_patrimonial', // duplicado con codigo_patrimonio
                'inventariador_id',
            ]);
        });

        // =============================================
        // 2. MODIFICAR TABLA ASSET_RESPONSIBLES
        // =============================================
        Schema::table('asset_responsibles', function (Blueprint $table) {
            // Agregar FKs a tablas de RRHH
            $table->uuid('area_id')->nullable()->after('id');
            $table->uuid('oficina_id')->nullable()->after('area_id');
            $table->uuid('contrato_id')->nullable()->after('oficina_id');
            
            // Agregar índices (no constraints por diferencia de tipos UUID)
            $table->index('area_id');
            $table->index('oficina_id');
            $table->index('contrato_id');
            
            // Eliminar campos de texto redundantes
            $table->dropColumn([
                'nombre_normalizado',
                'area',
                'tipo_contrato',
            ]);
        });

        // =============================================
        // 3. MODIFICAR TABLA ASSET_MOVEMENTS
        // =============================================
        Schema::table('asset_movements', function (Blueprint $table) {
            // Agregar FK a estados
            $table->foreignId('estado_id')->nullable()->after('responsable_id')
                  ->constrained('asset_states')->nullOnDelete();
            
            // Agregar FK a oficinas (ubicación)
            $table->uuid('oficina_id')->nullable()->after('estado_id');
            $table->index('oficina_id');
            
            // Eliminar campos redundantes
            $table->dropColumn([
                'ubicacion_actual',
                'responsable_nombre',
                'modalidad_responsable',
                'documento_id',
                'estado', // ahora es FK
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios en asset_movements
        Schema::table('asset_movements', function (Blueprint $table) {
            $table->dropForeign(['estado_id']);
            $table->dropIndex(['oficina_id']);
            $table->dropColumn(['estado_id', 'oficina_id']);
            
            $table->string('ubicacion_actual')->nullable();
            $table->string('responsable_nombre')->nullable();
            $table->string('modalidad_responsable')->nullable();
            $table->string('documento_id')->nullable();
            $table->string('estado')->nullable();
        });

        // Revertir cambios en asset_responsibles
        Schema::table('asset_responsibles', function (Blueprint $table) {
            $table->dropIndex(['area_id']);
            $table->dropIndex(['oficina_id']);
            $table->dropIndex(['contrato_id']);
            $table->dropColumn(['area_id', 'oficina_id', 'contrato_id']);
            
            $table->string('nombre_normalizado')->nullable();
            $table->string('area')->nullable();
            $table->string('tipo_contrato')->nullable();
        });

        // Revertir cambios en assets
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('responsable_id')->nullable()
                  ->constrained('asset_responsibles')->nullOnDelete();
            $table->string('estado')->default('BUENO');
            $table->string('marca')->nullable();
            $table->string('color')->nullable();
            $table->string('tipo_origen')->nullable();
            $table->string('tipo_modalidad')->nullable();
            $table->date('fecha_asignacion')->nullable();
            $table->string('oficina')->nullable();
            $table->string('fuente')->nullable();
            $table->string('tipo_registro')->nullable();
            $table->string('codigo_patrimonial')->nullable();
            $table->unsignedBigInteger('inventariador_id')->nullable();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['marca_id']);
            $table->dropForeign(['color_id']);
            $table->dropForeign(['origen_id']);
            $table->dropColumn(['marca_id', 'color_id', 'origen_id']);
            $table->renameColumn('denominacion', 'detalle_bien');
        });
    }
};
