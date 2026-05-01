<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Factura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Factura>
 */
class FacturaFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);

        return [
            'cliente_id'      => Cliente::factory(),
            'serie'           => 'C',
            'numero'          => fake()->unique()->numberBetween(1, 999),
            'subtotal'        => $subtotal,
            'iva_porcentaje'  => 0,
            'iva_monto'       => 0,
            'irpf_porcentaje' => 0,
            'irpf_monto'      => 0,
            'monto'           => $subtotal,
            'concepto'        => 'Clases batería',
            'estado'          => 'pendiente',
            'fecha_emision'   => fake()->dateTimeThisYear()->format('Y-m-d'),
            'fecha_evento'    => null,
        ];
    }
}
