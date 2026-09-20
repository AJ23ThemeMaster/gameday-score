<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles base
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $anotador = Role::firstOrCreate(['name' => 'anotador', 'guard_name' => 'web']);
        // DISI-81: rol gestor — solo puede administrar SU equipo asociado
        // y los atletas de ese equipo. El team_id del usuario define el scope.
        $gestor = Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

        // Permisos granulares para admin (gestiona todo)
        $adminPermissions = [
            // Gestionar ligas/torneos/equipos/categorias/atletas
            'manage leagues', 'manage tournaments', 'manage teams', 'manage categories', 'manage athletes',
            // Gestionar anotadores y referees
            'manage scorekeepers', 'manage referees',
            // Gestionar usuarios + roles (incluye asignar gestores)
            'manage users',
            // Gestionar juegos
            'manage games',
            // Anotar en cualquier juego
            'score any game',
        ];
        foreach ($adminPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $admin->givePermissionTo($perm);
        }

        // Permisos para anotador: solo puede ver y anotar donde está asignado
        $anotadorPermissions = [
            'view games', 'score assigned games',
        ];
        foreach ($anotadorPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $anotador->givePermissionTo($perm);
        }

        // DISI-81: permisos para gestor — solo los permisos base; el chequeo
        // de scope (team_id matching) lo hace User::isGestor() en cada controlador.
        $gestorPermissions = [
            'manage assigned team',
            'manage assigned team athletes',
        ];
        foreach ($gestorPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $gestor->givePermissionTo($perm);
        }

        // Asignar rol admin al usuario frank@gameday.test (owner actual)
        $frank = User::firstOrCreate(
            ['email' => 'frank@gameday.test'],
            [
                'name' => 'Frank Marval',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        );
        if (! $frank->hasRole('admin')) {
            $frank->assignRole('admin');
        }

        // Crear un usuario anotador de prueba
        $anotadorUser = User::firstOrCreate(
            ['email' => 'anotador@gameday.test'],
            [
                'name' => 'Anotador Demo',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        );
        if (! $anotadorUser->hasRole('anotador')) {
            $anotadorUser->assignRole('anotador');
        }
    }
}
