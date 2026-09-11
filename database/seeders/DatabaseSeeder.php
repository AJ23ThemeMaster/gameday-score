<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Usuario base (si no existe) - usado por GameSeeder para asignar owner
        User::firstOrCreate(
            ['email' => 'frank@gameday.test'],
            [
                'name' => 'Frank Marval',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            LeagueSeeder::class,
            StadiumSeeder::class,
            TeamSeeder::class,
            AthleteSeeder::class,
            ScorekeeperSeeder::class,
            RefereeSeeder::class,
            GameSeeder::class,
        ]);
    }
}
