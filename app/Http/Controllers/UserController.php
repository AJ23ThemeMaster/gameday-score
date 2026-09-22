<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRolesRequest;
use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
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

    /**
     * DISI-delegado: formulario para que el admin cree un usuario nuevo
     * desde el panel. Solo accesible para admins (middleware del constructor).
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('users.create', compact('roles', 'teams', 'categories'));
    }

    /**
     * DISI-delegado: crear un usuario nuevo desde el panel admin.
     * Asigna los roles enviados, el team_id y category_id opcionales.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'team_id' => $data['team_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'active' => $request->boolean('active', true),
        ]);

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        return redirect()
            ->route('users.edit', $user)
            ->with('status', "Usuario «{$user->name}» creado correctamente. Contraseña inicial asignada.");
    }

    public function edit(User $user): View
    {
        $user->load('roles');
        $roles = Role::orderBy('name')->get();
        $assigned = $user->roles->pluck('name')->all();
        $teams = Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles', 'assigned', 'teams', 'categories'));
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

        // DISI-80 + DISI-delegado: asignar (o quitar) equipo y categoria
        // asociados. Ambos opcionales y nullable.
        $user->team_id = $data['team_id'] ?? null;
        $user->category_id = $data['category_id'] ?? null;
        $user->save();

        return redirect()
            ->route('users.edit', $user)
            ->with('status', "Usuario «{$user->name}» actualizado correctamente.");
    }

    /**
     * Eliminar un usuario del sistema.
     *
     * Guardas de seguridad:
     *  - No se permite eliminarse a si mismo (boton oculto en la UI ademas).
     *  - No se permite eliminar al ultimo administrador del sistema,
     *    para no dejar el sistema sin owner.
     */
    public function destroy(User $user): RedirectResponse
    {
        // 1. Evitar auto-eliminacion
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario desde esta pantalla.');
        }

        // 2. Evitar eliminar al ultimo admin
        if ($user->isAdmin()) {
            $adminsCount = User::role('admin')->count();
            if ($adminsCount <= 1) {
                return redirect()
                    ->route('users.index')
                    ->with('error', "No puedes eliminar al único administrador del sistema.");
            }
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', "Usuario «{$name}» eliminado correctamente.");
    }
}
