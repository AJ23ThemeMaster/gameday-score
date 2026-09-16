<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Atletas') }}</h2>
            <a href="{{ route('athletes.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">
                + {{ __('Nuevo atleta') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                {{-- DISI-65: filtros (Nombre | Doc | N° | Pos. | Estado | Equipo | Categoria) --}}
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <form method="GET" action="{{ route('athletes.index') }}" class="grid grid-cols-2 sm:grid-cols-7 gap-2">
                        <input type="text" name="name" value="{{ $filters['name'] }}" placeholder="{{ __('Nombre') }}"
                               class="sm:col-span-2 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                        <input type="text" name="document_id" value="{{ $filters['document_id'] }}" placeholder="{{ __('Doc.') }}"
                               class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                        <input type="text" name="number" value="{{ $filters['number'] }}" placeholder="{{ __('N°') }}"
                               class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                        <select name="position" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                            <option value="">{{ __('Pos. (todas)') }}</option>
                            @foreach ($positions as $p)
                                <option value="{{ $p }}" {{ $filters['position'] === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                            <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>{{ __('Estado (todos)') }}</option>
                            <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>{{ __('Activos') }}</option>
                            <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>{{ __('Inactivos') }}</option>
                        </select>
                        <select name="team_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                            <option value="">{{ __('Equipo (todos)') }}</option>
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}" {{ (string) $filters['team_id'] === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <select name="category_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                            <option value="">{{ __('Categoria (todas)') }}</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" {{ (string) $filters['category_id'] === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <div class="col-span-2 sm:col-span-7 flex flex-wrap gap-2 mt-1">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md">
                                {{ __('Buscar') }}
                            </button>
                            @php
                                $hasFilters = $filters['name'] !== '' || $filters['document_id'] !== '' || $filters['number'] !== '' || $filters['position'] !== '' || $filters['status'] !== 'all' || $filters['team_id'] !== null || $filters['category_id'] !== null;
                            @endphp
                            @if ($hasFilters)
                                <a href="{{ route('athletes.index') }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-md">
                                    {{ __('Limpiar filtros') }}
                                </a>
                                <span class="text-xs text-gray-500 self-center">
                                    {{ __('Mostrando') }} {{ $filteredCount }} {{ __('de') }} {{ $totalAthletes }} {{ __('atletas') }}
                                </span>
                            @else
                                <span class="text-xs text-gray-500 self-center">{{ $totalAthletes }} {{ __('atletas en total') }}</span>
                            @endif
                        </div>
                    </form>
                </div>

                @if ($athletes->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        @if ($hasFilters)
                            <p class="mb-4">{{ __('No hay atletas que coincidan con el filtro.') }}</p>
                            <a href="{{ route('athletes.index') }}" class="text-indigo-600 hover:text-indigo-800 underline">{{ __('Limpiar filtros') }}</a>
                        @else
                            <p class="mb-4">{{ __('Aún no hay atletas registrados.') }}</p>
                            <a href="{{ route('athletes.create') }}" class="text-indigo-600 hover:text-indigo-800 underline">{{ __('Registrar el primer atleta') }}</a>
                        @endif
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Foto') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Nombre') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Equipo') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Categoria') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('N°') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Pos.') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('B/T') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($athletes as $a)
                                <tr>
                                    <td class="px-6 py-3">
                                        @if ($a->photoUrl)
                                            <img src="{{ $a->photoUrl }}" class="h-10 w-10 rounded-full object-cover bg-gray-100">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-semibold">
                                                {{ mb_substr($a->first_name, 0, 1) }}{{ mb_substr($a->last_name, 0, 1) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        <a href="{{ route('athletes.show', $a) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $a->full_name }}</a>
                                        @if ($a->document_id)<p class="text-xs text-gray-500">{{ $a->document_id }}</p>@endif
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-600">{{ $a->team->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-sm">
                                        @if ($a->category)
                                            <a href="{{ route('categories.show', $a->category) }}" class="text-indigo-600 hover:text-indigo-800">{{ $a->category->name }}</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-center text-sm">{{ $a->number ?? '—' }}</td>
                                    <td class="px-6 py-3 text-center text-sm font-mono">{{ $a->position ?? '—' }}</td>
                                    <td class="px-6 py-3 text-center text-xs">{{ $a->bats }}/{{ $a->throws }}</td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($a->active)
                                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('Activo') }}</span>
                                        @else
                                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ __('Inactivo') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-medium">
                                        <a href="{{ route('athletes.edit', $a) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('Editar') }}</a>
                                        <form action="{{ route('athletes.destroy', $a) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar a «{{ $a->full_name }}»?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-3 border-t border-gray-200">{{ $athletes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>