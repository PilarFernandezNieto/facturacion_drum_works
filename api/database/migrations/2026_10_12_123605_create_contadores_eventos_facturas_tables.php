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
        Schema::create('contadores_factura', function (Blueprint $table) {
            $table->id();
            $table->char('serie', 1);
            $table->unsignedSmallInteger(('anio'));
            $table->unsignedInteger(('ultimo'))->default(0);
            $table->timestamps();

            $table->unique(['serie', 'anio']);
        });

        DB::statement('
        INSERT INTO contadores_factura (serie, anio, ultimo, created_at, updated_at)
        SELECT serie, anio, MAX(numero), NOW(), NOW()
        FROM facturas
        WHERE numero IS NOT NULL
        GROUP BY serie, anio
        ');

        Schema::create('factura_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId(('factura_id'))->constrained('facturas')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->json('detalle')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contadores_factura');
        Schema::dropIfExists('factura_eventos');
    }
};
