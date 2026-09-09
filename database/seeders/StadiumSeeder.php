<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Stadium;
use Illuminate\Database\Seeder;

class StadiumSeeder extends Seeder
{
    public function run(): void
    {
        $stadiums = [
            [
                'name' => 'Estadio Universitario',
                'city' => 'Caracas',
                'state' => 'Distrito Capital',
                'address' => 'Av. Las Ciencias, Los Chaguaramos',
                'capacity' => 25000,
                'notes' => 'Sede de los Leones del Caracas.',
            ],
            [
                'name' => 'Estadio José Pérez Colmenares',
                'city' => 'Maracay',
                'state' => 'Aragua',
                'address' => 'Av. Principal, Maracay',
                'capacity' => 12000,
                'notes' => 'Sede de los Tigres de Aragua.',
            ],
            [
                'name' => 'Estadio Alfonso "Chico" Carrasquel',
                'city' => 'Puerto La Cruz',
                'state' => 'Anzoátegui',
                'address' => 'Av. Municipal, Puerto La Cruz',
                'capacity' => 18000,
                'notes' => 'Sede de los Caribes de Anzoátegui.',
            ],
        ];

        foreach ($stadiums as $st) {
            Stadium::updateOrCreate(['name' => $st['name']], $st);
        }
    }
}
