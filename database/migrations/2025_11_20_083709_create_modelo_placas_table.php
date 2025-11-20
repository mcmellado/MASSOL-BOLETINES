<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
{

    Schema::dropIfExists('modelo_placas');

    Schema::create('modelo_placas', function (Blueprint $table) {
        $table->id();
        $table->string('nombre')->unique();
        $table->integer('potencia_w')->nullable();
        $table->timestamps();
    });

    $baseModelos = [
        'YINGLI 330',
        'ELEK 270',
        'PEIMAN 420W',
        'MUNCHEN/ AS-6P-320W',
        'LONGI 445W',
        'LONGI 550W',
        'LONGI 555W',
        'LONGI 540W',
        'LONGI 545W',
        'LONGI 560W',
        'LONGI 570W',
        'LONGI 640W',
        'RISEN 270W',
        'RISEN 435W',
        'RISEN 400W',
        'RISEN 405W',
        'RISEN 410W',
        'RISEN 450W',
        'RISEN 545W',
    ];

    foreach ($baseModelos as $nombre) {
        $potencia = null;
        if (preg_match('/(\d+)\s*W?/i', $nombre, $m)) {
            $potencia = (int) $m[1];
        }

        DB::table('modelo_placas')->insert([
            'nombre'      => $nombre,
            'potencia_w'  => $potencia,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}

};
