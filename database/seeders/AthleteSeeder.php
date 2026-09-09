<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Athlete;
use App\Models\Team;
use Illuminate\Database\Seeder;

class AthleteSeeder extends Seeder
{
    public function run(): void
    {
        $rosters = [
            'LDC' => [
                ['first_name' => 'José', 'last_name' => 'Altuve', 'number' => 27, 'position' => '2B', 'bats' => 'R', 'throws' => 'R'],
                ['first_name' => 'Miguel', 'last_name' => 'Cabrera', 'number' => 24, 'position' => '1B', 'bats' => 'R', 'throws' => 'R'],
                ['first_name' => 'Ronald', 'last_name' => 'Acuña Jr.', 'number' => 13, 'position' => 'RF', 'bats' => 'R', 'throws' => 'R'],
            ],
            'TDA' => [
                ['first_name' => 'Luis', 'last_name' => 'Arraez', 'number' => 2, 'position' => '1B', 'bats' => 'L', 'throws' => 'R'],
                ['first_name' => 'Salvador', 'last_name' => 'Pérez', 'number' => 30, 'position' => 'C', 'bats' => 'R', 'throws' => 'R'],
                ['first_name' => 'Carlos', 'last_name' => 'Correa', 'number' => 4, 'position' => 'SS', 'bats' => 'R', 'throws' => 'R'],
            ],
            'CDA' => [
                ['first_name' => 'Ronald', 'last_name' => 'Guzmán', 'number' => 15, 'position' => '1B', 'bats' => 'L', 'throws' => 'L'],
                ['first_name' => 'Willson', 'last_name' => 'Contreras', 'number' => 40, 'position' => 'C', 'bats' => 'R', 'throws' => 'R'],
                ['first_name' => 'Anthony', 'last_name' => 'Rendon', 'number' => 6, 'position' => '3B', 'bats' => 'R', 'throws' => 'R'],
            ],
            'NDM' => [
                ['first_name' => 'José', 'last_name' => 'García', 'number' => 11, 'position' => 'SS', 'bats' => 'R', 'throws' => 'R'],
                ['first_name' => 'Andrés', 'last_name' => 'Giménez', 'number' => 0, 'position' => '2B', 'bats' => 'L', 'throws' => 'R'],
                ['first_name' => 'Francisco', 'last_name' => 'Álvarez', 'number' => 4, 'position' => 'C', 'bats' => 'R', 'throws' => 'R'],
            ],
        ];

        foreach ($rosters as $shortName => $players) {
            $team = Team::where('short_name', $shortName)->first();
            if (! $team) {
                continue;
            }
            foreach ($players as $player) {
                Athlete::updateOrCreate(
                    [
                        'first_name' => $player['first_name'],
                        'last_name' => $player['last_name'],
                    ],
                    array_merge($player, ['team_id' => $team->id]),
                );
            }
        }
    }
}
