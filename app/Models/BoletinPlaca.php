<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoletinPlaca extends Model
{
    use HasFactory;

    protected $table = 'boletin_placas';

    protected $fillable = [
        'boletin_id',
        'modelo_placa',
        'potencia_placa',
        'cantidad_placas',
    ];

    public function boletin()
    {
        return $this->belongsTo(Boletin::class);
    }
}
