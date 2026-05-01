<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialTest extends TestCase
{
    use RefreshDatabase;

    private function alumno(): Cliente
    {
        return Cliente::factory()->create(['tipo' => 'alumno', 'activo' => true]);
    }

    private function bolo(): Cliente
    {
        return Cliente::factory()->create(['tipo' => 'bolo', 'activo' => false]);
    }

    private function facturaC(Cliente $cliente, string $fecha, float $monto, string $estado = 'pendiente', int $numero = 1): Factura
    {
        return Factura::factory()->create([
            'cliente_id'      => $cliente->id,
            'serie'           => 'C',
            'numero'          => $numero,
            'subtotal'        => $monto,
            'iva_porcentaje'  => 0,
            'iva_monto'       => 0,
            'irpf_porcentaje' => 0,
            'irpf_monto'      => 0,
            'monto'           => $monto,
            'concepto'        => 'Clases batería',
            'estado'          => $estado,
            'fecha_emision'   => $fecha,
        ]);
    }

    private function facturaB(Cliente $cliente, string $fecha, float $monto, string $estado = 'pendiente', int $numero = 1): Factura
    {
        return Factura::factory()->create([
            'cliente_id'      => $cliente->id,
            'serie'           => 'B',
            'numero'          => $numero,
            'subtotal'        => $monto,
            'iva_porcentaje'  => 10,
            'iva_monto'       => $monto * 0.10,
            'irpf_porcentaje' => 15,
            'irpf_monto'      => $monto * 0.15,
            'monto'           => $monto,
            'concepto'        => 'Bolo',
            'estado'          => $estado,
            'fecha_emision'   => $fecha,
        ]);
    }

    // -------------------------------------------------------------------------

    public function test_requiere_autenticacion(): void
    {
        $this->getJson('/api/facturas/historial')
            ->assertUnauthorized();
    }

    public function test_devuelve_array_vacio_sin_facturas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/facturas/historial')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_agrupa_correctamente_un_mes_con_clases_y_bolos(): void
    {
        $user    = User::factory()->create();
        $alumno  = $this->alumno();
        $cliente = $this->bolo();

        $this->facturaC($alumno,  '2026-01-05', 80.00, 'pagada',    1);
        $this->facturaB($cliente, '2026-01-10', 200.00, 'pendiente', 1);

        $respuesta = $this->actingAs($user)
            ->getJson('/api/facturas/historial')
            ->assertOk()
            ->json();

        $this->assertCount(1, $respuesta);
        $fila = $respuesta[0];

        $this->assertEquals('2026-01', $fila['mes']);
        $this->assertEquals(1,      $fila['alumnos']);
        $this->assertEquals(1,      $fila['facturas_clases']);
        $this->assertEquals(80.00,  (float) $fila['total_clases']);
        $this->assertEquals(1,      $fila['bolos']);
        $this->assertEquals(200.00, (float) $fila['total_bolos']);
        $this->assertEquals(280.00, (float) $fila['total_facturado']);
        $this->assertEquals(80.00,  (float) $fila['total_cobrado']); // solo la pagada
    }

    public function test_alumnos_cuenta_clientes_distintos_no_facturas(): void
    {
        // Mismo alumno, dos facturas C en el mismo mes (caso anómalo) → debe contar 1 alumno
        $user   = User::factory()->create();
        $alumno = $this->alumno();

        $this->facturaC($alumno, '2026-02-01', 80.00, 'pendiente', 1);
        $this->facturaC($alumno, '2026-02-15', 80.00, 'pendiente', 2);

        $fila = $this->actingAs($user)
            ->getJson('/api/facturas/historial')
            ->assertOk()
            ->json(0);

        $this->assertEquals(1, $fila['alumnos']);
        $this->assertEquals(2, $fila['facturas_clases']);
    }

    public function test_total_cobrado_solo_suma_facturas_pagadas(): void
    {
        $user = User::factory()->create();
        $a1   = $this->alumno();
        $a2   = $this->alumno();

        $this->facturaC($a1, '2026-03-01', 80.00, 'pagada',    1);
        $this->facturaC($a2, '2026-03-01', 90.00, 'pendiente', 2);

        $fila = $this->actingAs($user)
            ->getJson('/api/facturas/historial')
            ->assertOk()
            ->json(0);

        $this->assertEquals(170.00, (float) $fila['total_facturado']);
        $this->assertEquals(80.00,  (float) $fila['total_cobrado']);
    }

    public function test_meses_se_devuelven_en_orden_descendente(): void
    {
        $user   = User::factory()->create();
        $alumno = $this->alumno();

        $this->facturaC($alumno, '2026-01-01', 80.00, 'pendiente', 1);
        $this->facturaC($alumno, '2026-03-01', 80.00, 'pendiente', 2);
        $this->facturaC($alumno, '2026-02-01', 80.00, 'pendiente', 3);

        $meses = array_column(
            $this->actingAs($user)->getJson('/api/facturas/historial')->json(),
            'mes'
        );

        $this->assertEquals(['2026-03', '2026-02', '2026-01'], $meses);
    }

    public function test_meses_de_años_distintos_no_se_mezclan(): void
    {
        $user   = User::factory()->create();
        $alumno = $this->alumno();

        $this->facturaC($alumno, '2025-12-01', 80.00, 'pagada', 1);
        $this->facturaC($alumno, '2026-01-01', 90.00, 'pagada', 2);

        $respuesta = $this->actingAs($user)
            ->getJson('/api/facturas/historial')
            ->assertOk()
            ->json();

        $this->assertCount(2, $respuesta);

        $porMes = array_column($respuesta, null, 'mes');

        $this->assertEquals(80.00, (float) $porMes['2025-12']['total_cobrado']);
        $this->assertEquals(90.00, (float) $porMes['2026-01']['total_cobrado']);
    }
}
