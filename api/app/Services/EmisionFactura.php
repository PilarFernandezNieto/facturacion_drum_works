<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\FacturaEvento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmisionFactura
{
    public function __construct(private NumeracionFactura $numeracion) {}

    /**
     * Emite un borrador: le asigna número definitivo y congel los datos del cliente.
     * Si la factura ya está emitida, la devuelve sin cambios.
     */

    public function emitir(Factura $factura, ?int $userId): Factura
    {
        return DB::transaction(function () use ($factura, $userId) {
            // Releer con bloqueo: dos peticiones simultáneas no pueden emitir la misma factura.
            $factura = Factura::lockForUpdate()->findOrFail($factura->id);

            if ($factura->situacion !== Factura::SITUACION_BORRADOR) {
                return $factura;
            }

            $cliente = $factura->cliente;
            $anio = Carbon::parse($factura->fecha_emision)->year;

            $factura->update([
                'numero' => $this->numeracion->siguiente($factura->serie, $anio),
                'situacion' => Factura::SITUACION_EMITIDA,
                'emitida_at' => now(),
                'emitida_por' => $userId,
                'cliente_snapshot' => [
                    'nombre' => $cliente->nombre,
                    'nif_cif' => $cliente->nif_cif,
                    'direccion' => $cliente->direccion,
                    'codigo_postal' => $cliente->codigo_postal,
                    'localidad' => $cliente->localidad,
                    'provincia' => $cliente->provincia

                ]
            ]);

            FacturaEvento::create([
                'factura_id' => $factura->id,
                'user_id' => $userId,
                'tipo' => 'emitida',
                'detalle' => ['codigo' => $factura->codigo]
            ]);
            return $factura;
        });
    }
}
