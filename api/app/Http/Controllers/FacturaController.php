<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $fechaActual = Carbon::now();
        $anio = $fechaActual->year;
        $mes = $fechaActual->month;

        // 1 query: IDs de alumnos que ya tienen factura C este mes
        $yaFacturados = Factura::where('serie', 'C')
            ->whereYear('fecha_emision', $anio)
            ->whereMonth('fecha_emision', $mes)
            ->pluck('cliente_id')
            ->toArray();

        $clientesPendientes = Cliente::where('tipo', 'alumno')
            ->where('activo', true)
            ->whereNotIn('id', $yaFacturados)
            ->get();

        if ($clientesPendientes->isEmpty()) {
            return response()->json([
                'mensaje' => 'Todos los alumnos ya tienen factura para este mes.',
                'total_generadas' => 0,
            ]);
        }

        // Último número de Serie C del año actual (1 query, antes del loop)
        $ultimoNumero = Factura::where('serie', 'C')
            ->whereYear('fecha_emision', $anio)
            ->max('numero') ?? 0;

        $concepto = 'Clases batería ' . strtoupper($fechaActual->translatedFormat('F'));
        $fechaEmision = $fechaActual->toDateString();
        $conteo = 0;

        DB::transaction(function () use ($clientesPendientes, &$ultimoNumero, &$conteo, $concepto, $fechaEmision) {
            foreach ($clientesPendientes as $cliente) {
                $ultimoNumero++;

                Factura::create([
                    'cliente_id'      => $cliente->id,
                    'serie'           => 'C',
                    'numero'          => $ultimoNumero,
                    'subtotal'        => $cliente->cuota_mensual,
                    'iva_porcentaje'  => 0,
                    'iva_monto'       => 0,
                    'irpf_porcentaje' => 0,
                    'irpf_monto'      => 0,
                    'monto'           => $cliente->cuota_mensual,
                    'concepto'        => $concepto,
                    'estado'          => 'pendiente',
                    'fecha_emision'   => $fechaEmision,
                ]);

                $conteo++;
            }
        });

        return response()->json([
            'mensaje' => "Se han generado $conteo facturas nuevas para la serie C.",
            'total_generadas' => $conteo,
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
            'subtotal' => 'required|numeric|min:0|max:999999.99',
            'iva_porcentaje' => 'required|numeric|min:0|max:100',
            'irpf_porcentaje' => 'required|numeric|min:0|max:100',
            'concepto' => 'required|string|max:500',
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

    public function historial()
    {
        $filas = Factura::selectRaw("
            DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
            COUNT(DISTINCT CASE WHEN serie = 'C' THEN cliente_id END) as alumnos,
            SUM(CASE WHEN serie = 'C' THEN 1 ELSE 0 END) as facturas_clases,
            SUM(CASE WHEN serie = 'C' THEN monto ELSE 0 END) as total_clases,
            SUM(CASE WHEN serie = 'B' THEN 1 ELSE 0 END) as bolos,
            SUM(CASE WHEN serie = 'B' THEN monto ELSE 0 END) as total_bolos,
            SUM(monto) as total_facturado,
            SUM(CASE WHEN estado = 'pagada' THEN monto ELSE 0 END) as total_cobrado
        ")
            ->groupByRaw("DATE_FORMAT(fecha_emision, '%Y-%m')")
            ->orderByRaw("mes DESC")
            ->get();

        return response()->json($filas);
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
