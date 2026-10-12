<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;


/**
 * Registro de eventos de una factura. Solo admite inserciones; es el historial
 * que justifica la secuencia de facturación y no debe alterarse
 */
class FacturaEvento extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'factura_eventos';

    protected $fillable = ['factura_id', 'user_id', 'tipo', 'detalle'];

    protected $casts = ['detalle' => 'array', 'created_at' => 'datetime'];


    protected static function booted(): void
    {
        static::updating(fn() => throw new LogicException('Los eventos de factura no se pueden modificar'));
        static::deleting(fn() => throw new LogicException('Los eventos de factura no se pueden eliminar'));
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
