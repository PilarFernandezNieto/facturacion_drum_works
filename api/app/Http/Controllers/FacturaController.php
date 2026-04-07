<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaController extends Controller
{
    public function index()
    {
        return response()->json(Factura::with('cliente')->orderBy('fecha_emision', 'desc')->get());
    }

    /**
     * Generación masiva de la serie C (Alumnos/Clases)
     */
    public function generarMasiva(Request $request)
    {
        $clientes = Cliente::where('tipo', 'alumno')->get();
        $fechaActual = Carbon::now();
        $anio = $fechaActual->year;
        $conteo = 0;

        foreach ($clientes as $cliente) {
            $existe = Factura::where('cliente_id', $cliente->id)
                ->where('serie', 'C')
                ->whereMonth('fecha_emision', $fechaActual->month)
                ->whereYear('fecha_emision', $fechaActual->year)
                ->exists();

            if (!$existe) {
                // Obtener siguiente número de serie C para el año actual
                $ultimoNumero = Factura::where('serie', 'C')
                    ->whereYear('fecha_emision', $anio)
                    ->max('numero') ?? 0;

                $mesNombre = $fechaActual->translatedFormat('F');
                $concepto = "Clases batería " . strtoupper($mesNombre);

                Factura::create([
                    'cliente_id' => $cliente->id,
                    'serie' => 'C',
                    'numero' => $ultimoNumero + 1,
                    'subtotal' => $cliente->cuota_mensual,
                    'iva_porcentaje' => 0,
                    'iva_monto' => 0,
                    'irpf_porcentaje' => 0,
                    'irpf_monto' => 0,
                    'monto' => $cliente->cuota_mensual,
                    'concepto' => $concepto,
                    'estado' => 'pendiente',
                    'fecha_emision' => $fechaActual->toDateString(),
                ]);
                $conteo++;
            }
        }

        return response()->json([
            'mensaje' => "Se han generado $conteo facturas nuevas para la serie C.",
            'total_generadas' => $conteo
        ]);
    }

    /**
     * Generación manual (principalmente para Serie B - Bolos)
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'serie' => 'required|in:C,B',
            'subtotal' => 'required|numeric',
            'iva_porcentaje' => 'required|numeric',
            'irpf_porcentaje' => 'required|numeric',
            'concepto' => 'required|string',
            'fecha_evento' => 'nullable|date',
            'fecha_emision' => 'required|date',
        ]);

        $anio = Carbon::parse($datos['fecha_emision'])->year;
        
        // Numeración correlativa
        $ultimoNumero = Factura::where('serie', $datos['serie'])
            ->whereYear('fecha_emision', $anio)
            ->max('numero') ?? 0;
        
        $ivaMonto = ($datos['subtotal'] * $datos['iva_porcentaje']) / 100;
        $irpfMonto = ($datos['subtotal'] * $datos['irpf_porcentaje']) / 100;
        $total = $datos['subtotal'] + $ivaMonto - $irpfMonto;

        $factura = Factura::create([
            'cliente_id' => $datos['cliente_id'],
            'serie' => $datos['serie'],
            'numero' => $ultimoNumero + 1,
            'subtotal' => $datos['subtotal'],
            'iva_porcentaje' => $datos['iva_porcentaje'],
            'iva_monto' => $ivaMonto,
            'irpf_porcentaje' => $datos['irpf_porcentaje'],
            'irpf_monto' => $irpfMonto,
            'monto' => $total,
            'concepto' => $datos['concepto'],
            'fecha_evento' => $datos['fecha_evento'],
            'fecha_emision' => $datos['fecha_emision'],
            'estado' => 'pendiente',
        ]);

        return response()->json([
            'mensaje' => 'Factura generada correctamente',
            'factura' => $factura
        ], 201);
    }

    public function actualizarEstado(Request $request, Factura $factura)
    {
        $request->validate(['estado' => 'required|in:pendiente,pagada']);
        $factura->update(['estado' => $request->estado]);
        return response()->json(['mensaje' => 'Estado actualizado.', 'factura' => $factura]);
    }

    public function destroy(Factura $factura)
    {
        $factura->delete();
        return response()->json(['mensaje' => 'Factura eliminada.']);
    }

    public function exportarPdf(Factura $factura)
    {
        $factura->load('cliente');
        
        $datos = [
            'fecha' => Carbon::parse($factura->fecha_emision)->format('d/m/Y'),
            'factura' => $factura,
            'cliente' => $factura->cliente,
            // Agregamos el código formateado para la vista
            'codigo_factura' => $factura->codigo
        ];

        $pdf = Pdf::loadView('pdf.factura', $datos);
        
        // Nombre del archivo basado en el código
        $nombreArchivo = "FRA " . str_replace('/', '-', $factura->codigo) . ".pdf";
        
        return $pdf->download($nombreArchivo);
    }
}
