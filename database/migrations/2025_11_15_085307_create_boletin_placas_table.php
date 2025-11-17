<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletin_placas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('boletin_id')->constrained('boletines')->onDelete('cascade');

            $table->string('modelo_placa');
            $table->string('potencia_placa')->nullable();
            $table->integer('cantidad_placas');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletin_placas');
    }
};
