<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Referee;
use Illuminate\Database\Seeder;

class RefereeSeeder extends Seeder
{
    public function run(): void
    {
        $referees = [
            [
                'first_name' => 'Carlos',
                'last_name' => 'Pérez',
                'document_id' => 'V-11223344',
                'phone' => '+58-412-1112222',
                'email' => 'carlos.perez@gameday.test',
                'certification' => 'Árbitro Principal LVBP',
                'notes' => '20 años arbitrando en la LVBP.',
            ],
            [
                'first_name' => 'Luis',
                'last_name' => 'Hernández',
                'document_id' => 'V-14567890',
                'phone' => '+58-414-3334444',
                'email' => 'luis.hernandez@gameday.test',
                'certification' => 'Árbitro de Bases',
                'notes' => 'Especialista en primera y tercera base.',
            ],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Silva',
                'document_id' => 'V-17890123',
                'phone' => '+58-416-5556666',
                'email' => 'roberto.silva@gameday.test',
                'certification' => 'Árbitro de Jardín',
                'notes' => 'Juez de left y right field.',
            ],
        ];

        foreach ($referees as $rf) {
            Referee::updateOrCreate(
                ['document_id' => $rf['document_id']],
                $rf,
            );
        }
    }
}
