<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'nif_cif',
        'email',
        'telefono',
        'direccion',
        'codigo_postal',
        'localidad',
        'provincia',
        'curso',
        'cuota_mensual',
        'tipo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }
}
