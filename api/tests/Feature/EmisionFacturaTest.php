<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaEvento;
use App\Models\User;
use App\Services\EmisionFactura;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmisionFacturaTest extends TestCase
{
    use RefreshDatabase;

    private function borrador(?Cliente $cliente = null): Factura
    {
        return Factura::factory()->create([
            'cliente_id'    => ($cliente ?? Cliente::factory()->create())->id,
            'serie'         => 'B',
            'numero'        => null,
            'situacion'     => Factura::SITUACION_BORRADOR,
            'fecha_emision' => '2026-05-10',
        ]);
    }

    private function emision(): EmisionFactura
    {
        return app(EmisionFactura::class);
    }

    public function test_emitir_asigna_numero_congela_cliente_y_registra_evento(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['nombre' => 'Ana Ruiz', 'nif_cif' => '12345678Z']);

        $factura = $this->emision()->emitir($this->borrador($cliente), $user->id)->fresh();

        $this->assertSame(1, $factura->numero);
        $this->assertSame('001B/2026', $factura->codigo);
        $this->assertSame('emitida', $factura->situacion);
        $this->assertNotNull($factura->emitida_at);
        $this->assertSame($user->id, $factura->emitida_por);
        $this->assertSame('12345678Z', $factura->cliente_snapshot['nif_cif']);
        $this->assertSame(1, FacturaEvento::where('factura_id', $factura->id)->where('tipo', 'emitida')->count());
    }

    public function test_emitir_dos_veces_no_consume_otro_numero(): void
    {
        $factura = $this->borrador();

        $this->emision()->emitir($factura, null);
        $this->emision()->emitir($factura, null);

        $this->assertSame(1, $factura->fresh()->numero);
        $this->assertSame(1, FacturaEvento::where('factura_id', $factura->id)->count());
    }

    public function test_cambiar_el_nif_del_cliente_no_altera_la_factura_emitida(): void
    {
        $cliente = Cliente::factory()->create(['nif_cif' => '11111111H']);
        $factura = $this->emision()->emitir($this->borrador($cliente), null);

        $cliente->update(['nif_cif' => '22222222J']);

        $this->assertSame('11111111H', $factura->fresh()->cliente_snapshot['nif_cif']);
    }

    public function test_el_numero_lo_da_el_orden_de_emision_no_el_de_creacion(): void
    {
        $primera = $this->borrador();
        $segunda = $this->borrador();

        $this->emision()->emitir($segunda, null);
        $this->emision()->emitir($primera, null);

        $this->assertSame(1, $segunda->fresh()->numero);
        $this->assertSame(2, $primera->fresh()->numero);
    }
    public function test_emitir_pone_la_fecha_de_emision_de_hoy_y_usa_su_anio(): void
    {
        $this->travelTo(Carbon::parse('2027-01-02 10:00', 'Europe/Madrid'));
        $factura = $this->borrador(); // creado con fecha 2026-05-10

        $factura = $this->emision()->emitir($factura, null)->fresh();

        $this->assertSame('2027-01-02', $factura->fecha_emision);
        $this->assertSame('001B/2027', $factura->codigo);
    }
}
