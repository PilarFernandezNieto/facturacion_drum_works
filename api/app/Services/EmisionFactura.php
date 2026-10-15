<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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



    /**
     * Garantiza que la factura que está emitida y tiene su PDF guardado.
     * Todo ocurre en una transacción: si falla el PDF, la emisión se revierte
     */

    public function asegurarPdf(Factura $factura, ?int $userId): Factura
    {
        return DB::transaction(function () use ($factura, $userId) {
            $factura = $this->emitir($factura, $userId);
            $factura = Factura::lockForUpdate()->findOrFail($factura->id);

            if ($factura->pdf_path) {
                if (!Storage::disk('local')->exists($factura->pdf_path)) {
                    throw new RuntimeException(("Falta el PDF guardado de la factura {$factura->codigo}"));
                }
                return $factura;
            }

            // Facturas anteriores a este sistema: no tienen datos congelados; se congelan ahora

            $origenLegacy = $factura->cliente_snapshot === null;
            if ($origenLegacy) {
                $cliente = $factura->cliente;
                $factura->cliente_snapshot = [
                    'nombre'        => $cliente->nombre,
                    'nif_cif'       => $cliente->nif_cif,
                    'direccion'     => $cliente->direccion,
                    'codigo_postal' => $cliente->codigo_postal,
                    'localidad'     => $cliente->localidad,
                    'provincia'     => $cliente->provincia,
                ];
            }

            $binario = Pdf::loadView('pdf.factura', [
                'fecha' => Carbon::parse($factura->fecha_emision)->format('d/m/Y'),
                'factura' => $factura,
                'cliente' => (object) $factura->cliente_snapshot,
                'codigo_factura' => $factura->codigo
            ])->output();


            $ruta = sprintf(
                'facturas/%d/%s-%03d.pdf',
                Carbon::parse($factura->fecha_emision)->year,
                $factura->serie,
                $factura->numero
            );
            Storage::disk('local')->put($ruta, $binario);
            $factura->pdf_path = $ruta;
            $factura->pdf_sha256 = hash('sha256', $binario);
            $factura->save();

            FacturaEvento::create([
                'factura_id' => $factura->id,
                'user_id'    => $userId,
                'tipo'       => 'pdf_generado',
                'detalle'    => [
                    'sha256' => $factura->pdf_sha256,
                    'origen' => $origenLegacy ? 'datos_actuales_del_cliente' : 'datos_congelados',
                ],
            ]);
            return $factura;
        });
    }
}
