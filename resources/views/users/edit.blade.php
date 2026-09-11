<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Asignar roles a') }}: <span class="text-indigo-700">{{ $user->name }}</span>
            </h2>
            <a href="{{ route('users.index') }}"
               class="text-sm text-gray-600 hover:text-gray-900 underline">
                {{ __('← Volver al listado') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="mb-6 flex items-center gap-4 pb-6 border-b border-gray-200">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                             class="h-16 w-16 rounded-full object-cover bg-gray-100">
                    @else
                        <div class="h-16 w-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-semibold">
                            {{ $user->avatar_initials }}
                        </div>
                    @endif
                    <div>
                        <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                        <p class="text-sm text-gray-600">{{ $user->email }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label :value="__('Roles del usuario')" />
                        <p class="mt-1 text-sm text-gray-600 mb-3">
                            {{ __('Marca los roles que tendrá este usuario. Los permisos se heredan de los roles asignados.') }}
                        </p>

                        @if ($roles->isEmpty())
                            <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-3">
                                {{ __('No hay roles registrados. Crea al menos uno desde') }}
                                <a href="{{ route('roles.index') }}" class="underline">{{ __('Roles y permisos') }}</a>.
                            </div>
                        @else
                            <div class="space-y-2 border border-gray-200 rounded-md p-3 bg-gray-50">
                                @foreach ($roles as $role)
                                    <label class="flex items-start bg-white px-3 py-2 rounded border border-gray-200 hover:border-indigo-300 cursor-pointer">
                                        <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                               {{ in_array($role->name, $assigned, true) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-0.5 mr-3">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $role->name }}
                                                @if (in_array($role->name, ['admin', 'anotador'], true))
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700">
                                                        {{ __('Sistema') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                {{ $role->permissions->count() }} {{ __('permiso(s) asociados') }}
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                        <x-input-error :messages="$errors->get('roles.*')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('users.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900 underline">
                            {{ __('Cancelar') }}
                        </a>
                        <x-primary-button>
                            {{ __('Guardar roles') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
