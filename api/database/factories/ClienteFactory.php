<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre'        => fake()->name(),
            'nif_cif'       => strtoupper(fake()->bothify('?########')),
            'email'         => fake()->unique()->safeEmail(),
            'telefono'      => fake()->phoneNumber(),
            'direccion'     => fake()->streetAddress(),
            'codigo_postal' => fake()->postcode(),
            'localidad'     => fake()->city(),
            'provincia'     => fake()->state(),
            'tipo'          => 'alumno',
            'activo'        => true,
            'curso'         => null,
            'cuota_mensual' => 80.00,
        ];
    }
}
