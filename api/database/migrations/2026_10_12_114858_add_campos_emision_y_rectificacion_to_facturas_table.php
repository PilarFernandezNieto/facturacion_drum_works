<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            //un borrador no tiene número todavía
            $table->integer('numero')->nullable()->change();

            // Años derivado de fecha_emision; sirve para el índice único (serie, anio, numero)
            $table->unsignedSmallInteger('anio')->storedAs('YEAR(fecha_emision)');

            $table->string('situacion', 15)->default('emitida');
            $table->timestamp('emitida_at')->nullable();
            $table->foreignId('emitida_por')->nullable()->constrained('users')->restrictOnDelete();

            $table->foreignId('rectifica_a_id')->nullable()->constrained('facturas')->restrictOnDelete();
            $table->string('metodo_rectificacion', 15)->nullable();
            $table->string('motivo_codigo', 30)->nullable();
            $table->string('motivo_texto')->nullable();

            $table->json('cliente_snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_sha256', 64)->nullable();
        });
        DB::table('facturas')->update(['emitida_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['emitida_por']);
            $table->dropForeign(['rectifica_a_id']);
            $table->dropColumn([
                'anio',
                'situacion',
                'emitida_at',
                'emitida_por',
                'rectifica_a_id',
                'metodo_rectificacion',
                'motivo_codigo',
                'motivo_texto',
                'cliente_snapshot',
                'pdf_path',
                'pdf_sha256'
            ]);
        });
    }
};
