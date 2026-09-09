<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Pre-Infantil',
                'slug' => 'pre-infantil',
                'description' => 'Categoría para jugadores de 8 a 10 años. Reglas simplificadas y 5 innings.',
                'innings_count' => 5,
                'mercy_rule_difference' => 15,
                'mercy_rule_inning' => 4,
                'pitch_limit' => 50,
            ],
            [
                'name' => 'Infantil',
                'slug' => 'infantil',
                'description' => 'Categoría para jugadores de 11 a 12 años. 6 innings.',
                'innings_count' => 6,
                'mercy_rule_difference' => 12,
                'mercy_rule_inning' => 4,
                'pitch_limit' => 70,
            ],
            [
                'name' => 'Juvenil',
                'slug' => 'juvenil',
                'description' => 'Categoría para jugadores de 13 a 17 años. 7 innings.',
                'innings_count' => 7,
                'mercy_rule_difference' => 10,
                'mercy_rule_inning' => 5,
                'pitch_limit' => 95,
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'description' => 'Categoría profesional. 9 innings, reglas oficiales.',
                'innings_count' => 9,
                'mercy_rule_difference' => 10,
                'mercy_rule_inning' => 7,
                'pitch_limit' => 110,
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
