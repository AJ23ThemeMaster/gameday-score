<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Game;
use App\Models\Stadium;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        $category = Category::where('slug', 'juvenil')->first();
        $stadium = Stadium::where('name', 'Estadio Universitario')->first();
        $home = Team::where('short_name', 'LDC')->first();
        $away = Team::where('short_name', 'TDA')->first();

        if (! $category || ! $stadium || ! $home || ! $away) {
            return;
        }

        // Juego programado (privado, futuro)
        Game::updateOrCreate(
            [
                'user_id' => $user->id,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'scheduled_at' => now()->addDays(7)->setTime(18, 30),
            ],
            [
                'category_id' => $category->id,
                'stadium_id' => $stadium->id,
                'status' => 'scheduled',
                'is_public' => false,
                'innings_count' => $category->innings_count,
                'mercy_rule_difference' => $category->mercy_rule_difference,
                'mercy_rule_inning' => $category->mercy_rule_inning,
                'pitch_limit' => $category->pitch_limit,
            ],
        );

        // Juego público (en progreso) para probar la vista publica del DISI-8
        Game::updateOrCreate(
            [
                'user_id' => $user->id,
                'home_team_id' => $away->id,
                'away_team_id' => $home->id,
                'scheduled_at' => now()->subHour(),
            ],
            [
                'category_id' => $category->id,
                'stadium_id' => $stadium->id,
                'status' => 'in_progress',
                'is_public' => true,
                'public_token' => Str::random(48),
                'home_score' => 2,
                'away_score' => 1,
                'current_inning' => 4,
                'inning_half' => 'top',
                'balls' => 2,
                'strikes' => 1,
                'outs' => 1,
                'bases' => ['first' => true, 'second' => false, 'third' => true],
                'innings_count' => $category->innings_count,
                'mercy_rule_difference' => $category->mercy_rule_difference,
                'mercy_rule_inning' => $category->mercy_rule_inning,
                'pitch_limit' => $category->pitch_limit,
                'started_at' => now()->subHour(),
            ],
        );
    }
}
