<div class="space-y-4">

    <div>
        <x-input-label for="name" :value="__('Identificador (slug, en minúsculas)')" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                      :value="old('name', $role->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Solo letras minúsculas, números y guiones. No se puede cambiar después de creado.') }}</p>
    </div>

    <div>
        <x-input-label :value="__('Permisos')" />
        <div class="mt-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-96 overflow-y-auto p-3 bg-wv-bg rounded-md border border-wv-border">
            @php
                // Permissions se sirven por nombre (Spatie\Permission\Models\Permission).
                // El RoleController hace syncPermissions($request->input('permissions', []))
                // que acepta tanto id como name; usamos name para mantener consistencia
                // con la convencion del seeder y del resto del sistema.
                //
                // Nota: la version reescrita en d24384d referenciaba App\Models\Permission
                // con columnas display_name y group que nunca se crearon; eso fue rollback
                // a la logica del commit original af28aee.
                $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();
            @endphp
            @forelse ($allPermissions as $perm)
                <label class="flex items-center gap-2 text-sm hover:bg-wv-surface-hover px-2 py-1 rounded cursor-pointer text-wv-text">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                           {{ in_array($perm->name, old('permissions', isset($role) ? $role->permissions->pluck('name')->toArray() : []), true) ? 'checked' : '' }}
                           class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
                    <span>{{ $perm->name }}</span>
                </label>
            @empty
                <p class="md:col-span-2 lg:col-span-3 text-sm text-wv-text-secondary italic">
                    {{ __('No hay permisos registrados. Ejecuta php artisan db:seed --class=RoleSeeder para crearlos.') }}
                </p>
            @endforelse
        </div>
        <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
        <x-input-error :messages="$errors->get('permissions.*')" class="mt-2" />
    </div>

</div>