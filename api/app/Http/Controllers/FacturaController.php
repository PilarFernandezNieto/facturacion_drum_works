<?php

namespace App\Http\Controllers;

use App\Http\Requests\FacturaRequest;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaEvento;
use App\Services\EmisionFactura;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


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


        $concepto = 'Clases batería ' . strtoupper($fechaActual->translatedFormat('F'));
        $fechaEmision = $fechaActual->toDateString();
        $userId = $request->user()?->id;



        $conteo = DB::transaction(function () use ($anio, $mes, $concepto, $fechaEmision, $userId) {
            // Alumnos que ya tienen factura C este mes (borrador o emitida)

            $yaFacturados = Factura::where('serie', 'C')
                ->whereYear('fecha_emision', $anio)
                ->whereMonth('fecha_emision', $mes)
                ->pluck('cliente_id')
                ->toArray();

            $clientesPendientes = Cliente::where('tipo', 'alumno')
                ->where('activo', true)
                ->whereNotIn('id', $yaFacturados)
                ->get();


            foreach ($clientesPendientes as $cliente) {
                $factura = Factura::create([
                    'cliente_id'      => $cliente->id,
                    'serie'           => 'C',
                    'situacion' => Factura::SITUACION_BORRADOR,
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
                FacturaEvento::create([
                    'factura_id' => $factura->id,
                    'user_id' => $userId,
                    'tipo' => 'borrador_creado'
                ]);
            }
            return $clientesPendientes->count();
        });

        if ($conteo === 0) {
            return response()->json([
                'mensaje' => 'Todos los alumnos ya tienen factura para este mes',
                'total_generadas' => 0
            ]);
        }

        return response()->json([
            'mensaje' => "Se han generado $conteo borradores nuevos para la serie C.",
            'total_generadas' => $conteo,
        ]);
    }

    /**
     * Generación manual (principalmente para Serie B - Bolos)
     */
    public function store(FacturaRequest $request)
    {

        $datos = $request->validated();

        $ivaMonto = ($datos['subtotal'] * $datos['iva_porcentaje']) / 100;
        $irpfMonto = ($datos['subtotal'] * $datos['irpf_porcentaje']) / 100;
        $total = $datos['subtotal'] + $ivaMonto - $irpfMonto;

        $factura = DB::transaction(function () use ($request, $datos, $ivaMonto, $irpfMonto, $total) {
            $factura = Factura::create([
                'cliente_id' => $datos['cliente_id'],
                'serie' => $datos['serie'],
                'situacion' => Factura::SITUACION_BORRADOR,
                'subtotal' => $datos['subtotal'],
                'iva_porcentaje' => $datos['iva_porcentaje'],
                'iva_monto' => $ivaMonto,
                'irpf_porcentaje' => $datos['irpf_porcentaje'],
                'irpf_monto' => $irpfMonto,
                'monto' => $total,
                'concepto' => $datos['concepto'],
                'fecha_evento' => $datos['fecha_evento'] ?? null,
                'fecha_emision' => $datos['fecha_emision'],
                'estado' => 'pendiente',
            ]);

            FacturaEvento::create([
                'factura_id' => $factura->id,
                'user_id' => $request->user()?->id,
                'tipo' => 'borrador_creado'
            ]);
            return $factura;
        });

        return response()->json([
            'mensaje' => 'Borrador de factura creado correctamente',
            'factura' => $factura
        ], 201);
    }

    public function update(FacturaRequest $request, Factura $factura)
    {
        if ($factura->situacion !== Factura::SITUACION_BORRADOR) {
            return response()->json([
                'mensaje' => 'Sólo se pueden editar borradores.'
            ], 409);
        }
        $datos = $request->validated();
        $ivaMonto = ($datos['subtotal'] * $datos['iva_porcentaje']) / 100;
        $irpfMonto = ($datos['subtotal'] * $datos['irpf_porcentaje']) / 100;
        $total = $datos['subtotal'] + $ivaMonto - $irpfMonto;

        $factura->update([
            'cliente_id' => $datos['cliente_id'],
            'subtotal' => $datos['subtotal'],
            'iva_porcentaje' => $datos['iva_porcentaje'],
            'iva_monto' => $ivaMonto,
            'irpf_porcentaje' => $datos['irpf_porcentaje'],
            'irpf_monto' => $irpfMonto,
            'monto' => $total,
            'concepto' => $datos['concepto'],
            'fecha_evento' => $datos['fecha_evento'] ?? null,
            'fecha_emision' => $datos['fecha_emision'],
        ]);
        return response()->json([
            'mensaje' => 'Borrador actualizado correctamente',
            'factura' => $factura
        ]);
    }

    public function actualizarEstado(Request $request, Factura $factura)
    {
        $request->validate(['estado' => 'required|in:pendiente,pagada']);

        if ($factura->situacion === Factura::SITUACION_BORRADOR) {
            return response()->json(
                ['mensaje' => 'Un borrador no puede marcarse como pagado. Emítelo primero descargando el PDF.'],
                409
            );
        }
        $factura->update(['estado' => $request->estado]);
        return response()->json(['mensaje' => 'Estado actualizado.', 'factura' => $factura]);
    }
    public function destroy(Factura $factura)
    {
        if ($factura->situacion !== Factura::SITUACION_BORRADOR) {
            return response()->json(
                ['mensaje' => 'Sólo se pueden eliminar borradores. Una factura emitida debe rectificarse.'],
                409
            );
        }
        $factura->update(['borrado' => true]);
        return response()->json(['mensaje' => 'Factura eliminada.']);
    }


    public function historial()
    {
        $filas = Factura::where('situacion', '<>', Factura::SITUACION_BORRADOR)->selectRaw("
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

    public function exportarPdf(Request $request, Factura $factura, EmisionFactura $emision)
    {
        $factura = $emision->asegurarPdf($factura, $request->user()?->id);

        $nombreArchivo = 'FRA ' . str_replace('/', '-', $factura->codigo) . '.pdf';
        return Storage::disk('local')->download($factura->pdf_path, $nombreArchivo);
    }
}
