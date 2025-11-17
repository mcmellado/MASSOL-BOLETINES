<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');

            $table->date('fecha');
            $table->string('numero_registro')->nullable();
            $table->string('cups')->nullable();
            $table->string('referencia_catastral')->nullable();

            // Potencia contratada en factura de luz
            $table->string('potencia_factura_luz')->nullable();

            $table->string('metros_cuadrados_vivienda')->nullable();

            // 👉 Potencia pico TOTAL de la instalación (suma placas = potencia_placa * cantidad)
            $table->decimal('potencia_pico', 10, 2)->nullable();

            $table->string('marca_inversor');
            $table->string('modelo_inversor')->nullable();
            $table->string('potencia_inversores')->nullable();

            $table->string('tipo_instalacion_electrica');
            $table->string('tension_suministro');
            $table->string('tipo_instalacion');

            $table->json('tipos_cubierta')->nullable();

            $table->boolean('tiene_bateria')->default(false);
            $table->string('potencia_bateria')->nullable();
            $table->integer('numero_baterias')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletines');
    }
};
