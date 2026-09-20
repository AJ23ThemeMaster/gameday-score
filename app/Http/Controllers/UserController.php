<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRolesRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(): View
    {
        $users = User::with('roles')
            ->orderBy('name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function edit(User $user): View
    {
        $user->load('roles');
        $roles = Role::orderBy('name')->get();
        $assigned = $user->roles->pluck('name')->all();
        $teams = Team::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles', 'assigned', 'teams'));
    }

    public function update(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        // Evitar que el último admin pierda su rol admin (bloqueo de seguridad)
        $roles = $request->input('roles', []);
        if ($user->isAdmin() && ! in_array('admin', $roles, true)) {
            $adminsCount = User::role('admin')->count();
            if ($adminsCount <= 1) {
                return redirect()
                    ->route('users.edit', $user)
                    ->with('error', 'No puedes quitar el rol admin al único administrador del sistema.');
            }
        }

        $data = $request->validated();
        $user->syncRoles($roles);

        // DISI-80: asignar (o quitar) equipo asociado. Nullable.
        $user->team_id = $data['team_id'] ?? null;
        $user->save();

        return redirect()
            ->route('users.edit', $user)
            ->with('status', "Usuario «{$user->name}» actualizado correctamente.");
    }
}
