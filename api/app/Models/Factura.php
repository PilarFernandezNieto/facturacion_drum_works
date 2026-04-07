<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'serie',
        'numero',
        'subtotal',
        'iva_porcentaje',
        'iva_monto',
        'irpf_porcentaje',
        'irpf_monto',
        'monto', // Usaremos este campo para el TOTAL final
        'concepto',
        'fecha_evento',
        'estado',
        'fecha_emision'
    ];

    protected $appends = ['codigo'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Devuelve el código de factura formateado: XXX[C|B]/YYYY
     */
    public function getCodigoAttribute()
    {
        $anio = \Carbon\Carbon::parse($this->fecha_emision)->year;
        $numStr = str_pad($this->numero, 3, '0', STR_PAD_LEFT);
        return $numStr . $this->serie . '/' . $anio;
    }
}
