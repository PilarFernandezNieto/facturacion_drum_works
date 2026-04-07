<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\FacturaController;

// Autenticación pública
Route::post('/registro', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/usuario', function (Request $request) {
        return $request->user();
    });

    // CRUD Clientes
    Route::apiResource('clientes', ClienteController::class);

    // Gestión de Facturas
    Route::get('facturas', [FacturaController::class, 'index']);
    Route::post('facturas', [FacturaController::class, 'store']);
    Route::post('facturas/generar-masiva', [FacturaController::class, 'generarMasiva']);
    Route::put('facturas/{factura}/estado', [FacturaController::class, 'actualizarEstado']);
    Route::delete('facturas/{factura}', [FacturaController::class, 'destroy']);
    Route::get('facturas/{factura}/pdf', [FacturaController::class, 'exportarPdf']);
});
