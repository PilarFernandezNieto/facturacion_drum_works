<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    public const SITUACION_BORRADOR = 'borrador';
    public const SITUACION_EMITIDA = 'emitida';
    public const SITUACION_RECTIFICADA = 'rectificada';


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
        'fecha_emision',
        'borrado',
        'situacion',
        'emitida_at',
        'emitida_por',
        'rectifica_a_id',
        'metodo_rectificacion',
        'motivo_codigo',
        'motivo_texto',
        'cliente_snapshot',
        'pdf_path',
        'pdf_sha256'
    ];

    protected $appends = ['codigo'];
    protected $hidden = ['pdf_path'];

    protected $casts = ['numero' => 'integer', 'emitida_at' => 'datetime', 'cliente_snapshot' => 'array'];

    /**
     * Excluye por defecto las facturas marcadas como borradas (soft delete).
     */
    protected static function booted()
    {
        static::addGlobalScope('noBorradas', function ($builder) {
            $builder->where('borrado', false);
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function emitidaPor()
    {
        return $this->belongsTo(User::class, 'emitida_por');
    }

    /** Factura original a la que rectifica esta (si es una rectificativa) */
    public function rectificada()
    {
        return $this->belongsTo(Factura::class, 'rectifica_a_id');
    }

    /** Rectificativas emitidas sobre esta factura */
    public function rectificativas()
    {
        return $this->hasMany(Factura::class, 'rectifica_a_id');
    }

    public function eventos()
    {
        return $this->hasMany(FacturaEvento::class)->orderBy('id');
    }

    /**
     * Devuelve el código de factura formateado: XXX[C|B]/YYYY
     */
    public function getCodigoAttribute()
    {
        if ($this->numero === null) {
            return null;
        }
        $anio = \Carbon\Carbon::parse($this->fecha_emision)->year;
        $numStr = str_pad($this->numero, 3, '0', STR_PAD_LEFT);
        return $numStr . $this->serie . '/' . $anio;
    }
}
