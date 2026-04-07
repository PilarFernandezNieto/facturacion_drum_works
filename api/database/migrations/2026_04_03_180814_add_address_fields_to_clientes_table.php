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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('codigo_postal', 10)->nullable()->after('email');
            $table->string('localidad', 100)->nullable()->after('codigo_postal');
            $table->string('provincia', 100)->nullable()->after('localidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['codigo_postal', 'localidad', 'provincia']);
        });
    }
};
