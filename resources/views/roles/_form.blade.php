{{--
    Formulario compartido para crear/editar rol.
    Variables esperadas:
      - $role (instancia de Role, puede ser nueva)
      - $permissions (array de strings con los nombres de todos los permisos)
      - $assigned (array de strings con los nombres de permisos ya asignados) [solo en edit]
--}}
@php
    $assigned = $assigned ?? old('permissions', []);
    $isSystem = isset($role) && $role->exists && in_array($role->name, ['admin', 'anotador'], true);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('Nombre del rol')" />
        <x-text-input id="name" name="name" type="text"
                      class="block mt-1 w-full"
                      :value="old('name', $role->name ?? '')"
                      {{ $isSystem ? 'readonly' : '' }}
                      required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">
            {{ __('Solo letras, números, guion (-) y guion bajo (_).') }}
            @if ($isSystem)
                <span class="text-amber-600 font-semibold">{{ __('Este es un rol del sistema y su nombre no puede modificarse.') }}</span>
            @endif
        </p>
    </div>

    <div>
        <x-input-label :value="__('Permisos del rol')" />
        <p class="mt-1 text-sm text-gray-600 mb-3">
            {{ __('Marca los permisos que tendrá este rol.') }}
        </p>

        @if (empty($permissions))
            <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-3">
                {{ __('No hay permisos registrados en el sistema. Ejecuta php artisan db:seed --class=RoleSeeder para crearlos.') }}
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 border border-gray-200 rounded-md p-3 bg-gray-50">
                @foreach ($permissions as $permission)
                    <label class="flex items-center text-sm text-gray-700 bg-white px-3 py-2 rounded border border-gray-200 hover:border-indigo-300 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                               {{ in_array($permission, (array) $assigned, true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-2">
                        <span>{{ $permission }}</span>
                    </label>
                @endforeach
            </div>
        @endif
        <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
        <x-input-error :messages="$errors->get('permissions.*')" class="mt-2" />
    </div>
</div>
