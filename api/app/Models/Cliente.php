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
        'tipo' // alumno o bolo
    ];

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }
}
