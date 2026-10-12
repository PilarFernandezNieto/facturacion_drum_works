<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GeneracionFacturasTest extends TestCase
{
    use RefreshDatabase;

    private function alumno(bool $activo = true): Cliente
    {
        return Cliente::factory()->create([
            'tipo' => 'alumno',
            'activo' => $activo,
            'cuota_mensual' => 80,
        ]);
    }

    public function test_masiva_numera_correlativamente_y_solo_activos(): void
    {
        $user = User::factory()->create();
        $this->alumno();
        $this->alumno();
        $this->alumno();
        $this->alumno(false);

        $this->actingAs($user)
            ->postJson('/api/facturas/generar-masiva')
            ->assertOk()
            ->assertJsonPath('total_generadas', 3);

        $numeros = Factura::where('serie', 'C')->orderBy('numero')->pluck('numero')->all();
        $this->assertSame([1, 2, 3], $numeros);

        $this->assertSame(
            3,
            (int) DB::table('contadores_factura')->where('serie', 'C')->where('anio', now()->year)->value('ultimo')
        );
    }

    public function test_masiva_es_idempotente_en_el_mismo_mes(): void
    {
        $user = User::factory()->create();
        $this->alumno();
        $this->alumno();

        $this->actingAs($user)->postJson('/api/facturas/generar-masiva')->assertOk();

        $this->actingAs($user)
            ->postJson('/api/facturas/generar-masiva')
            ->assertOk()
            ->assertJsonPath('total_generadas', 0);

        $this->assertSame(2, Factura::where('serie', 'C')->count());
        $this->assertSame(
            2,
            (int) DB::table('contadores_factura')->where('serie', 'C')->value('ultimo')
        );
    }

    public function test_store_numera_correlativamente_sin_fecha_evento(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['tipo' => 'bolo']);

        $datos = [
            'cliente_id' => $cliente->id,
            'serie' => 'B',
            'subtotal' => 100,
            'iva_porcentaje' => 10,
            'irpf_porcentaje' => 15,
            'concepto' => 'Bolo de prueba',
            'fecha_emision' => '2026-05-10',
        ];

        $this->actingAs($user)->postJson('/api/facturas', $datos)
            ->assertCreated()
            ->assertJsonPath('factura.numero', 1);

        $this->actingAs($user)->postJson('/api/facturas', $datos)
            ->assertCreated()
            ->assertJsonPath('factura.numero', 2);
    }
}
