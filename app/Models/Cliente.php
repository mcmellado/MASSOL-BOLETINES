<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'dni_cif',
        'email',
        'telefono',
        'direccion',
        'poblacion',
        'provincia',
        'codigo_postal', 
    ];

    public function boletines()
    {
        return $this->hasMany(Boletin::class);
    }
}
