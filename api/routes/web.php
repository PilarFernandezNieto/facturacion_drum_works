<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/preview-factura', function () {
    $cliente = (object)[
        'nombre'    => 'Álvaro Bárcena Suárez',
        'nif_cif'   => '09413027R',
        'direccion' => "C/José Menéndez Carreño \"Cuchichi\" 8, 4ºE",
        'codigo_postal' => '33011',
        'localidad' => 'Oviedo',
        'provincia' => 'Asturias'
    ];

    $factura = (object)[
        'concepto'         => 'Concierto de Los Commodoros en La Puerta de Cimadevilla, Oviedo',
        'fecha_evento'     => '2025-12-04',
        'subtotal'         => 200.00,
        'iva_porcentaje'   => 10,
        'iva_monto'        => 20.00,
        'irpf_porcentaje'  => 15,
        'irpf_monto'       => 30.00,
        'monto'            => 190.00,
    ];

    return view('pdf.factura', [
        'codigo_factura' => '014B/2025',
        'fecha'          => '08/12/2025',
        'cliente'        => $cliente,
        'factura'        => $factura,
    ]);
});
