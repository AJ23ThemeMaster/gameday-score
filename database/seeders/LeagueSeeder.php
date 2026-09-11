<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\League;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = [
            [
                'name' => 'Federación Venezolana de Béisbol',
                'short_name' => 'FVB',
                'country' => 'Venezuela',
                'description' => 'Máximo organismo del béisbol organizado en Venezuela. Regula el béisbol profesional y amateur a nivel nacional.',
                'active' => true,
            ],
            [
                'name' => 'Criollitos de Venezuela',
                'short_name' => 'CVB',
                'country' => 'Venezuela',
                'description' => 'Programa de béisbol formativo para niños de 5 a 12 años en Venezuela. Una de las ligas infantiles más grandes del país.',
                'active' => true,
            ],
            [
                'name' => 'Pequeñas Ligas de Béisbol de Venezuela',
                'short_name' => 'PLBV',
                'country' => 'Venezuela',
                'description' => 'Filial venezolana de Little League Baseball. Participa en torneos internacionales con jugadores de 10 a 12 años.',
                'active' => true,
            ],
            [
                'name' => 'Liga de Béisbol Pony de Venezuela',
                'short_name' => 'PONY',
                'country' => 'Venezuela',
                'description' => 'Filial de PONY Baseball. Compite en categorías Bronce (11-12), Plata (13-14), Oro (15-16) y Puro (17-18).',
                'active' => true,
            ],
            [
                'name' => 'New Concept Showcase',
                'short_name' => 'NCS',
                'country' => 'Venezuela',
                'description' => 'Showcase y torneo de béisbol de nueva generación, orientado a la promoción de talento juvenil venezolano para el béisbol universitario y profesional.',
                'active' => true,
            ],
        ];

        foreach ($leagues as $data) {
            League::updateOrCreate(
                ['name' => $data['name']],
                $data,
            );
        }
    }
}
