<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($users->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        {{ __('No hay usuarios registrados.') }}
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                                    {{ __('Usuario') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                                    {{ __('Correo electrónico') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                                    {{ __('Roles') }}
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">
                                    {{ __('Acciones') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($user->avatar_url)
                                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                                     class="h-8 w-8 rounded-full object-cover bg-gray-100">
                                            @else
                                                <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                                                    {{ $user->avatar_initials }}
                                                </div>
                                            @endif
                                            <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($user->roles->isEmpty())
                                            <span class="text-xs text-gray-400">{{ __('Sin rol') }}</span>
                                        @else
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($user->roles as $role)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        {{ $role->name === 'admin' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-50 text-indigo-700' }}">
                                                        {{ $role->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            {{ __('Asignar roles') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
