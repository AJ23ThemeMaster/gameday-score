<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Scorekeeper;
use Illuminate\Database\Seeder;

class ScorekeeperSeeder extends Seeder
{
    public function run(): void
    {
        $scorekeepers = [
            [
                'first_name' => 'María',
                'last_name' => 'Rodríguez',
                'document_id' => 'V-12345678',
                'phone' => '+58-412-1234567',
                'email' => 'maria.rodriguez@gameday.test',
                'notes' => 'Anotadora oficial LVBP, 10 años de experiencia.',
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Martínez',
                'document_id' => 'V-15678901',
                'phone' => '+58-414-7654321',
                'email' => 'pedro.martinez@gameday.test',
                'notes' => 'Especialista en categorías juveniles.',
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'González',
                'document_id' => 'V-18901234',
                'phone' => '+58-416-9876543',
                'email' => 'ana.gonzalez@gameday.test',
                'notes' => 'Anotadora auxiliar con experiencia en sófbol.',
            ],
        ];

        foreach ($scorekeepers as $sk) {
            Scorekeeper::updateOrCreate(
                ['document_id' => $sk['document_id']],
                $sk,
            );
        }
    }
}
