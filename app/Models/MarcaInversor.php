<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarcaInversor extends Model
{
    protected $table = 'marca_inversores';

    protected $fillable = [
        'nombre',
    ];
}
