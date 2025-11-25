<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inversores', function (Blueprint $table) {
            $table->id();

            // Relación con boletines
            $table->foreignId('boletin_id')
                  ->constrained('boletines')
                  ->onDelete('cascade');

            $table->string('marca');
            $table->string('modelo')->nullable();
            $table->string('potencia')->nullable();   // o decimal si quieres
            $table->integer('cantidad')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inversores');
    }
};
