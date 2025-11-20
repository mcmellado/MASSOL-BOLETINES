<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marca_inversores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Marcas base (las que usabas en el controller)
        $baseMarcas = [
            'Huawei',
            'Fronius',
            'Solax',
            'Victron',
            'SMA',
            'Kostal',
            'FOX',
        ];

        foreach ($baseMarcas as $m) {
            DB::table('marca_inversores')->insert([
                'nombre'     => $m,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marca_inversores');
    }
};
