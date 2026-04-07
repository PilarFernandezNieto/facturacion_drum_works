<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Renombrar la tabla estudiantes a clientes
        Schema::rename('estudiantes', 'clientes');

        // 2. Renombrar la columna extranjera en facturas
        Schema::table('facturas', function (Blueprint $table) {
            $table->renameColumn('estudiante_id', 'cliente_id');
        });

        // 3. Actualizar los valores de 'clase' a 'alumno'
        DB::table('clientes')
            ->where('tipo', 'clase')
            ->update(['tipo' => 'alumno']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revertir la actualización de valores
        DB::table('clientes')
            ->where('tipo', 'alumno')
            ->update(['tipo' => 'clase']);

        // 2. Revertir el nombre de la columna extranjera
        Schema::table('facturas', function (Blueprint $table) {
            $table->renameColumn('cliente_id', 'estudiante_id');
        });

        // 3. Revertir el nombre de la tabla
        Schema::rename('clientes', 'estudiantes');
    }
};
