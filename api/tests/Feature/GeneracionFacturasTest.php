<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    private function datosBolo(Cliente $cliente): array
    {
        return [
            'cliente_id' => $cliente->id,
            'serie' => 'B',
            'subtotal' => 100,
            'iva_porcentaje' => 10,
            'irpf_porcentaje' => 15,
            'concepto' => 'Bolo de prueba',
            'fecha_emision' => '2026-05-10',
        ];
    }

    public function test_masiva_crea_borradores_sin_numero_solo_de_alumnos_activos(): void
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

        $facturas = Factura::where('serie', 'C')->get();
        $this->assertCount(3, $facturas);
        $this->assertSame([null], $facturas->pluck('numero')->unique()->values()->all());
        $this->assertSame(['borrador'], $facturas->pluck('situacion')->unique()->values()->all());
        $this->assertSame(3, FacturaEvento::where('tipo', 'borrador_creado')->count());
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
    }

    public function test_store_crea_un_borrador_sin_numero_ni_fecha_evento(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['tipo' => 'bolo']);

        $this->actingAs($user)->postJson('/api/facturas', $this->datosBolo($cliente))
            ->assertCreated()
            ->assertJsonPath('factura.situacion', 'borrador')
            ->assertJsonPath('factura.numero', null)
            ->assertJsonPath('factura.codigo', null);
    }

    public function test_crear_borradores_no_consume_numeros(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['tipo' => 'bolo']);

        $this->actingAs($user)->postJson('/api/facturas', $this->datosBolo($cliente))->assertCreated();
        $this->actingAs($user)->postJson('/api/facturas', $this->datosBolo($cliente))->assertCreated();

        $this->assertDatabaseCount('contadores_factura', 0);
    }

    public function test_flujo_completo_crear_y_descargar_emite_con_numero(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['tipo' => 'bolo']);

        $id = $this->actingAs($user)->postJson('/api/facturas', $this->datosBolo($cliente))
            ->assertCreated()
            ->json('factura.id');

        $this->actingAs($user)->get("/api/facturas/{$id}/pdf")->assertOk();

        $factura = Factura::find($id);
        $this->assertSame('emitida', $factura->situacion);
        $this->assertSame(1, $factura->numero);
        $this->assertSame('001B/2026', $factura->codigo);
    }

    public function test_el_historial_no_cuenta_los_borradores(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['tipo' => 'bolo']);

        $this->actingAs($user)->postJson('/api/facturas', $this->datosBolo($cliente))->assertCreated();

        $this->actingAs($user)
            ->getJson('/api/facturas/historial')
            ->assertOk()
            ->assertExactJson([]);
    }
}
