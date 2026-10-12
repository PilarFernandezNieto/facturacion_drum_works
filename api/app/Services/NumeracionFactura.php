<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use LogicException;

class NumeracionFactura
{
    /**
     * Reserva y devuelve el siguiente número de la serie y año indicados.
     *
     * Debe llamarse dentro de DB::transaction(): el bloqueo de la fila del contador
     * se mantiene hasta que la transacción termina, y si ésta se revierte el número
     * no se consume.
     */
    public function siguiente(string $serie, int $anio): int
    {


        if (DB::transactionLevel() === 0) {
            throw new LogicException('La numeración debe reservarse dentro de una transacción.');
        }

        // Crea el contador si aún no existe; si ya existe no hace nada.
        DB::table('contadores_factura')->insertOrIgnore([
            'serie'      => $serie,
            'anio'       => $anio,
            'ultimo'     => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bloquea la fila hasta el final de la transacción.
        $ultimo = DB::table('contadores_factura')
            ->where('serie', $serie)
            ->where('anio', $anio)
            ->lockForUpdate()
            ->value('ultimo');



        $siguiente = $ultimo + 1;

        DB::table('contadores_factura')
            ->where('serie', $serie)
            ->where('anio', $anio)
            ->update(['ultimo' => $siguiente, 'updated_at' => now()]);



        return $siguiente;
    }
}
