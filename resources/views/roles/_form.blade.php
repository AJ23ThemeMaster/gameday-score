<div class="space-y-4">

    <div>
        <x-input-label for="name" :value="__('Identificador (slug, en minúsculas)')" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                      :value="old('name', $role->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Solo letras minúsculas, números y guiones. No se puede cambiar después de creado.') }}</p>
    </div>

    <div>
        <x-input-label for="display_name" :value="__('Nombre mostrado')" />
        <x-text-input id="display_name" name="display_name" type="text" class="block mt-1 w-full"
                      :value="old('display_name', $role->display_name ?? '')" required />
        <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" :value="__('Descripción')" />
        <textarea id="description" name="description" rows="3"
                  class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">{{ old('description', $role->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label :value="__('Permisos')" />
        <div class="mt-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-96 overflow-y-auto p-3 bg-wv-bg rounded-md border border-wv-border">
            @php
                $allPermissions = \App\Models\Permission::orderBy('group')->orderBy('name')->get();
                $grouped = $allPermissions->groupBy('group');
            @endphp
            @foreach ($grouped as $groupName => $perms)
                <div class="md:col-span-2 lg:col-span-3 mt-2 first:mt-0">
                    <p class="text-xs font-semibold text-wv-text-secondary uppercase tracking-wider">{{ ucfirst($groupName) }}</p>
                </div>
                @foreach ($perms as $perm)
                    <label class="flex items-center gap-2 text-sm hover:bg-wv-surface-hover px-2 py-1 rounded cursor-pointer text-wv-text">
                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                               {{ in_array($perm->id, old('permissions', isset($role) ? $role->permissions->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
                        <span>{{ $perm->display_name ?? $perm->name }}</span>
                    </label>
                @endforeach
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
        <x-input-error :messages="$errors->get('permissions.*')" class="mt-2" />
    </div>

</div>