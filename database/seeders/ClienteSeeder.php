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

        $provincias = [
            'Madrid' => ['Madrid', 'Alcorcón', 'Leganés', 'Getafe', 'Móstoles', 'Fuenlabrada', 'Parla'],
            'Barcelona' => ['Barcelona', 'Badalona', 'Hospitalet', 'Sabadell', 'Terrassa', 'Mataró'],
            'Valencia' => ['Valencia', 'Torrent', 'Gandía', 'Paterna', 'Sagunto', 'Xàtiva'],
            'Sevilla' => ['Sevilla', 'Dos Hermanas', 'Alcalá de Guadaíra', 'Utrera', 'Coria del Río'],
        ];

        foreach ($provincias as $provincia => $poblaciones) {

            for ($i = 0; $i < 150; $i++) {

                Cliente::create([
                    'nombre'            => $faker->firstName(),
                    'primer_apellido'   => $faker->lastName(),
                    'segundo_apellido'  => $faker->lastName(),

                    'dni_cif'           => $this->generarDNI(),
                    'email'             => $faker->unique()->safeEmail(),
                    'telefono'          => $faker->numerify('6########'),

                    'direccion'         => $faker->streetAddress(),
                    'poblacion'         => $faker->randomElement($poblaciones),
                    'provincia'         => $provincia,
                    'codigo_postal'     => $faker->postcode(),
                ]);
            }
        }
    }

    private function generarDNI(): string
    {
        $numero = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
        return $numero . $letras[$numero % 23];
    }
}
