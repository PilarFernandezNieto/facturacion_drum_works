<?php

namespace Tests\Feature;

use App\Services\NumeracionFactura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class NumeracionFacturaTest extends TestCase
{
    use RefreshDatabase;

    private function numeracion(): NumeracionFactura
    {
        return new NumeracionFactura();
    }

    public function test_empieza_en_uno_y_es_correlativo(): void
    {
        $this->assertSame(1, $this->numeracion()->siguiente('B', 2026));
        $this->assertSame(2, $this->numeracion()->siguiente('B', 2026));
        $this->assertSame(3, $this->numeracion()->siguiente('B', 2026));
    }

    public function test_cada_serie_y_anio_tiene_su_propia_secuencia(): void
    {
        $this->assertSame(1, $this->numeracion()->siguiente('B', 2026));
        $this->assertSame(1, $this->numeracion()->siguiente('C', 2026));
        $this->assertSame(1, $this->numeracion()->siguiente('B', 2027));
        $this->assertSame(2, $this->numeracion()->siguiente('B', 2026));
    }

    public function test_continua_desde_un_contador_existente(): void
    {
        DB::table('contadores_factura')->insert([
            'serie' => 'B',
            'anio' => 2026,
            'ultimo' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(29, $this->numeracion()->siguiente('B', 2026));
    }

    public function test_si_la_operacion_falla_el_numero_no_se_consume(): void
    {
        try {
            DB::transaction(function () {
                $this->numeracion()->siguiente('B', 2026);
                throw new RuntimeException('fallo simulado');
            });
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame(1, $this->numeracion()->siguiente('B', 2026));
    }
}
