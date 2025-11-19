<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boletin extends Model
{
    use HasFactory;

    protected $table = 'boletines';

    protected $fillable = [
        'cliente_id',
        'fecha',
        'numero_registro',
        'cups',
        'referencia_catastral',
        'potencia_factura_luz',
        'metros_cuadrados_vivienda',
        'potencia_pico',
        'marca_inversor',
        'modelo_inversor',
        'potencia_inversores',
        'tipo_instalacion_electrica',
        'tension_suministro',
        'tipo_instalacion',
        'tipos_cubierta',
        'tiene_bateria',
        'potencia_bateria',
        'numero_baterias',
        'proteccion_sobretension',
        'numero_inversores'
    ];

    protected $casts = [
        'tipos_cubierta' => 'array',
        'tiene_bateria' => 'boolean',
        'fecha' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function placas()
    {
        return $this->hasMany(BoletinPlaca::class);
    }
}
