<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use Faker\Factory as Faker;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        for ($i = 0; $i < 50; $i++) {
            Cliente::create([
                'nombre'            => $faker->firstName(),
                'primer_apellido'   => $faker->lastName(),
                'segundo_apellido'  => $faker->lastName(),
                'dni_cif'           => $this->generarDNI(),
                'email'             => $faker->unique()->safeEmail(),
                'telefono'          => $faker->numerify('6########'),
                'direccion'         => $faker->streetAddress(),
                'poblacion'         => $faker->city(),
                'provincia'         => $faker->state(),
                'codigo_postal'     => $faker->postcode(),
            ]);
        }
    }

    private function generarDNI(): string
    {
        // Número aleatorio de 8 cifras
        $numero = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);

        // Letras DNI
        $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
        $letra = $letras[$numero % 23];

        return $numero . $letra;
    }
}
