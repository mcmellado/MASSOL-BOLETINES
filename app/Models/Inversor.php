<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inversor extends Model
{
    use HasFactory;

    protected $table = 'inversores';

    protected $fillable = [
        'boletin_id',
        'marca',
        'modelo',
        'potencia',
        'cantidad',
    ];

    public function boletin()
    {
        return $this->belongsTo(Boletin::class);
    }
}
