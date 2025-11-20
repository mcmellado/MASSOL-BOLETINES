<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloPlaca extends Model
{
    protected $table = 'modelo_placas';

    protected $fillable = [
        'nombre',
        'potencia_w',
    ];
}
