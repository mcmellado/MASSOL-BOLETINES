<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->string('primer_apellido');
            $table->string('segundo_apellido');

            $table->string('dni_cif');
            $table->string('email');
            $table->string('telefono');

            $table->string('direccion');
            $table->string('poblacion');
            $table->string('provincia');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
