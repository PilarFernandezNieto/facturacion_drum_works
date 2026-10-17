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
}
