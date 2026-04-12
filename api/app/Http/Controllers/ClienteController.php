<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Listar todos los clientes.
     */
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($request->filled('tipo')) {
            $request->validate(['tipo' => 'in:alumno,bolo']);
            $query->where('tipo', $request->tipo);
        }

        return response()->json($query->get());
    }

    /**
     * Almacenar un nuevo cliente.
     */
    public function store(Request $request)
    {
        $reglas = [
            'nombre' => 'required|string|max:255',
            'nif_cif' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',
            'localidad' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'curso' => 'nullable|string|max:100',
            'cuota_mensual' => 'required_if:tipo,alumno|nullable|numeric|min:0',
            'tipo' => 'required|in:alumno,bolo',
        ];

        $datosValidados = $request->validate($reglas);

        $cliente = Cliente::create($datosValidados);

        return response()->json([
            'mensaje' => 'Registro guardado correctamente.',
            'cliente' => $cliente
        ], 201);
    }

    /**
     * Mostrar un cliente específico.
     */
    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    /**
     * Actualizar los datos de un cliente.
     */
    public function update(Request $request, Cliente $cliente)
    {
        $reglas = [
            'nombre' => 'sometimes|required|string|max:255',
            'nif_cif' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',
            'localidad' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'curso' => 'nullable|string|max:100',
            'cuota_mensual' => 'sometimes|nullable|numeric|min:0',
            'tipo' => 'sometimes|required|in:alumno,bolo',
        ];

        $datosValidados = $request->validate($reglas);

        $cliente->update($datosValidados);

        return response()->json([
            'mensaje' => 'Datos actualizados correctamente.',
            'cliente' => $cliente
        ]);
    }

    /**
     * Eliminar un cliente.
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->json([
            'mensaje' => 'Cliente eliminado correctamente.'
        ]);
    }
}
