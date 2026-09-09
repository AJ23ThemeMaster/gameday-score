<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Leones del Caracas',
                'short_name' => 'LDC',
                'city' => 'Caracas',
                'home_color' => '#1a3d6e',
                'away_color' => '#ffffff',
            ],
            [
                'name' => 'Tigres de Aragua',
                'short_name' => 'TDA',
                'city' => 'Maracay',
                'home_color' => '#f9a01b',
                'away_color' => '#1c1c1c',
            ],
            [
                'name' => 'Caribes de Anzoátegui',
                'short_name' => 'CDA',
                'city' => 'Puerto La Cruz',
                'home_color' => '#006633',
                'away_color' => '#f1c40f',
            ],
            [
                'name' => 'Navegantes del Magallanes',
                'short_name' => 'NDM',
                'city' => 'Valencia',
                'home_color' => '#0a2e5c',
                'away_color' => '#c41e3a',
            ],
        ];

        foreach ($teams as $team) {
            Team::updateOrCreate(['short_name' => $team['short_name']], $team);
        }
    }
}
