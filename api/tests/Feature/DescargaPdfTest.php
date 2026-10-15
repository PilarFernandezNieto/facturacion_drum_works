<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DescargaPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function borrador(Cliente $cliente): Factura
    {
        return Factura::factory()->create([
            'cliente_id'    => $cliente->id,
            'serie'         => 'B',
            'numero'        => null,
            'situacion'     => Factura::SITUACION_BORRADOR,
            'fecha_emision' => '2026-05-10',
        ]);
    }

    public function test_descargar_un_borrador_lo_emite_y_guarda_el_pdf(): void
    {
        $user = User::factory()->create();
        $factura = $this->borrador(Cliente::factory()->create());

        $respuesta = $this->actingAs($user)->get("/api/facturas/{$factura->id}/pdf")->assertOk();

        $factura = $factura->fresh();
        $this->assertSame('emitida', $factura->situacion);
        $this->assertSame(1, $factura->numero);
        Storage::disk('local')->assertExists($factura->pdf_path);
        $this->assertSame(hash('sha256', $respuesta->streamedContent()), $factura->pdf_sha256);
    }

    public function test_segunda_descarga_entrega_el_mismo_archivo_aunque_cambie_el_cliente(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['nif_cif' => '11111111H']);
        $factura = $this->borrador($cliente);

        $primera = $this->actingAs($user)->get("/api/facturas/{$factura->id}/pdf")->streamedContent();

        $cliente->update(['nif_cif' => '22222222J']);

        $segunda = $this->actingAs($user)->get("/api/facturas/{$factura->id}/pdf")->streamedContent();

        $this->assertSame($primera, $segunda);
        $this->assertSame(1, $factura->fresh()->numero);
        $this->assertSame(1, FacturaEvento::where('factura_id', $factura->id)->where('tipo', 'pdf_generado')->count());
    }

    public function test_factura_anterior_al_sistema_congela_datos_actuales_en_su_primera_descarga(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['nif_cif' => '33333333P']);
        $factura = Factura::factory()->create([
            'cliente_id'    => $cliente->id,
            'serie'         => 'B',
            'numero'        => 5,
            'fecha_emision' => '2026-02-01',
        ]);

        $this->actingAs($user)->get("/api/facturas/{$factura->id}/pdf")->assertOk();

        $factura = $factura->fresh();
        $this->assertSame(5, $factura->numero);
        $this->assertSame('33333333P', $factura->cliente_snapshot['nif_cif']);
        $this->assertNotNull($factura->pdf_sha256);
    }
}
