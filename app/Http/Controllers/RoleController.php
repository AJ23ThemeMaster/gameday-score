<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(): View
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15);

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::orderBy('name')->pluck('name')->all();
        $role = new Role();

        return view('roles.create', compact('permissions', 'role'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->string('name')->lower()->toString(),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->input('permissions', []));

        return redirect()
            ->route('roles.show', $role)
            ->with('status', "Rol «{$role->name}» creado correctamente.");
    }

    public function show(Role $role): View
    {
        $role->load('permissions');
        $users = $role->users()->orderBy('name')->paginate(15, ['id', 'name', 'email']);

        return view('roles.show', [
            'role' => $role,
            'users' => $users,
        ]);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('name')->pluck('name')->all();
        $assigned = $role->permissions->pluck('name')->all();

        return view('roles.edit', compact('role', 'permissions', 'assigned'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        // No permitir renombrar admin o anotador (roles semilla)
        if (in_array($role->name, ['admin', 'anotador'], true)) {
            return redirect()
                ->route('roles.show', $role)
                ->with('error', "El rol «{$role->name}» es un rol del sistema y no puede renombrarse.");
        }

        $role->update([
            'name' => $request->string('name')->lower()->toString(),
        ]);

        $role->syncPermissions($request->input('permissions', []));

        return redirect()
            ->route('roles.show', $role)
            ->with('status', "Rol «{$role->name}» actualizado correctamente.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Roles semilla no se eliminan
        if (in_array($role->name, ['admin', 'anotador'], true)) {
            return redirect()
                ->route('roles.index')
                ->with('error', "El rol «{$role->name}» es un rol del sistema y no puede eliminarse.");
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', "No se puede eliminar el rol «{$role->name}» porque {$usersCount} usuario(s) lo tienen asignado.");
        }

        $name = $role->name;
        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('status', "Rol «{$name}» eliminado correctamente.");
    }
}
