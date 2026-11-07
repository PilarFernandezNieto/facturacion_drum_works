<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\FacturaEvento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class FacturaModeloTest extends TestCase
{
    use RefreshDatabase;

    public function test_factura_nueva_es_emitida_por_defecto_y_tiene_codigo(): void
    {
        $factura = Factura::factory()
            ->create(['serie' => 'B', 'numero' => 7, 'fecha_emision' => '2026-03-10'])
            ->fresh();

        $this->assertSame('emitida', $factura->situacion);
        $this->assertSame('007B/2026', $factura->codigo);
        $this->assertSame(2026, (int) $factura->anio);
    }

    public function test_codigo_es_null_si_no_hay_numero(): void
    {
        $borrador = new Factura(['numero' => null, 'serie' => 'C', 'fecha_emision' => '2026-03-10']);

        $this->assertNull($borrador->codigo);
    }

    public function test_relaciones_entre_original_y_rectificativa(): void
    {
        $original = Factura::factory()->create(['serie' => 'B', 'numero' => 1]);
        $rectificativa = Factura::factory()->create([
            'serie'          => 'R',
            'numero'         => 1,
            'rectifica_a_id' => $original->id,
        ]);

        $this->assertTrue($original->rectificativas->contains($rectificativa));
        $this->assertTrue($rectificativa->rectificada->is($original));
    }

    public function test_un_evento_no_se_puede_modificar(): void
    {
        $evento = FacturaEvento::create([
            'factura_id' => Factura::factory()->create()->id,
            'tipo'       => 'prueba',
        ]);

        $this->expectException(LogicException::class);
        $evento->update(['tipo' => 'otro']);
    }

    public function test_un_evento_no_se_puede_eliminar(): void
    {
        $evento = FacturaEvento::create([
            'factura_id' => Factura::factory()->create()->id,
            'tipo'       => 'prueba',
        ]);

        $this->expectException(LogicException::class);
        $evento->delete();
    }
}
