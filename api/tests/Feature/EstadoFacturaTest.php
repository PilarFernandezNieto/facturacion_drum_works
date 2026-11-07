<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadoFacturaTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_puede_marcar_pagado_un_borrador(): void
    {
        $user = User::factory()->create();
        $alumno = Cliente::factory()->create([
            'tipo' => 'alumno',
            'activo' => true,
            'cuota_mensual' => 80,
        ]);

        // La generación masiva crea borradores
        $this->actingAs($user)->postJson('/api/facturas/generar-masiva');
        $borrador = Factura::where('cliente_id', $alumno->id)->first();

        $this->actingAs($user)
            ->putJson("/api/facturas/{$borrador->id}/estado", ['estado' => 'pagada'])
            ->assertStatus(409);

        $this->assertSame('pendiente', $borrador->fresh()->estado);
    }

    public function test_se_puede_eliminar_un_borrador(): void
    {
        $user = User::factory()->create();
        $alumno = Cliente::factory()->create([
            'tipo' => 'alumno',
            'activo' => true,
            'cuota_mensual' => 80,
        ]);
        $this->actingAs($user)->postJson('/api/facturas/generar-masiva');
        $borrador = Factura::where('cliente_id', $alumno->id)->first();

        $this->actingAs($user)
            ->deleteJson("/api/facturas/{$borrador->id}")
            ->assertOk();

        $this->assertNull(Factura::find($borrador->id));
    }

    public function test_no_se_puede_eliminar_una_factura_emitida(): void
    {
        $user = User::factory()->create();
        $alumno = Cliente::factory()->create([
            'tipo' => 'alumno',
            'activo' => true,
            'cuota_mensual' => 80,
        ]);
        $this->actingAs($user)->postJson('/api/facturas/generar-masiva');
        $factura = Factura::where('cliente_id', $alumno->id)->first();
        $factura->update(['situacion' => Factura::SITUACION_EMITIDA]);

        $this->actingAs($user)
            ->deleteJson("/api/facturas/{$factura->id}")
            ->assertStatus(409);

        $this->assertNotNull(Factura::find($factura->id));
    }
    public function test_se_puede_editar_un_borrador_y_recalcula_el_total(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['tipo' => 'bolo']);
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'serie' => 'B',
            'numero' => null,
            'situacion' => Factura::SITUACION_BORRADOR,
        ]);

        $this->actingAs($user)
            ->putJson("/api/facturas/{$factura->id}", [
                'cliente_id' => $cliente->id,
                'subtotal' => 200,
                'iva_porcentaje' => 10,
                'irpf_porcentaje' => 15,
                'concepto' => 'Concierto corregido',
                'fecha_evento' => '2026-06-01',
                'fecha_emision' => '2026-06-02',
            ])
            ->assertOk();

        $factura->refresh();
        $this->assertSame('Concierto corregido', $factura->concepto);
        $this->assertEquals(190, $factura->monto); // 200 + 20 de IVA - 30 de IRPF
    }

    public function test_no_se_puede_editar_una_factura_emitida(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['tipo' => 'bolo']);
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'serie' => 'B',
            'numero' => 1,
            'situacion' => Factura::SITUACION_EMITIDA,
        ]);

        $this->actingAs($user)
            ->putJson("/api/facturas/{$factura->id}", [
                'cliente_id' => $cliente->id,
                'subtotal' => 200,
                'iva_porcentaje' => 10,
                'irpf_porcentaje' => 15,
                'concepto' => 'Intento de cambio',
                'fecha_emision' => '2026-06-02',
            ])
            ->assertStatus(409);

        $this->assertNotSame('Intento de cambio', $factura->fresh()->concepto);
    }
}
