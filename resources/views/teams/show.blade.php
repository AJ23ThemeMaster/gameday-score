<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-3">
                @if ($team->logoUrl)
                    <img src="{{ $team->logoUrl }}" alt="{{ $team->name }}" class="h-10 w-10 object-contain">
                @endif
                {{ __('Equipo') }}: {{ $team->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('teams.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                    {{ __('Listado') }}
                </a>
                <a href="{{ route('teams.edit', $team) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md">
                    {{ __('Editar') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @include('partials._flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex flex-wrap items-center gap-3 mb-4">
                    @if ($team->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ __('Activo') }}
                        </span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            {{ __('Inactivo') }}
                        </span>
                    @endif
                    @if ($team->short_name)
                        <code class="text-sm text-gray-500">{{ $team->short_name }}</code>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Ciudad') }}</dt>
                        <dd class="text-base font-medium text-gray-900">{{ $team->city ?? '—' }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Atletas') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $team->athletes_count }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Como local') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $team->home_games_count }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-gray-500 text-xs uppercase">{{ __('Como visitante') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $team->away_games_count }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-md p-4 border" :style="'background-color: ' + '{{ $team->home_color ?? '#1a3d6e' }}' + '20'">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-8 h-8 rounded border border-gray-300"
                                  style="background-color: {{ $team->home_color ?? '#1a3d6e' }}"></span>
                            <span class="text-sm text-gray-700">{{ __('Color local') }}: <code>{{ $team->home_color ?? '—' }}</code></span>
                        </div>
                    </div>
                    <div class="rounded-md p-4 border">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-8 h-8 rounded border border-gray-300"
                                  style="background-color: {{ $team->away_color ?? '#ffffff' }}"></span>
                            <span class="text-sm text-gray-700">{{ __('Color visitante') }}: <code>{{ $team->away_color ?? '—' }}</code></span>
                        </div>
                    </div>
                </div>

                {{-- DISI-64: roster filtrable (Nombre | N° | Pos. | Estado | Categoria) --}}
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-sm font-semibold text-gray-700">
                            {{ __('Roster') }}
                            <span class="text-gray-500 font-normal">({{ $filteredCount }} {{ __('de') }} {{ $totalAthletes }})</span>
                        </h4>
                        @if ($teamCategories->count())
                            <a href="{{ route('categories.create', ['team_id' => $team->id]) }}"
                               class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">
                                + {{ __('Nueva categoria') }}
                            </a>
                        @endif
                    </div>

                    {{-- Filtros --}}
                    <form method="GET" action="{{ route('teams.show', $team) }}" class="grid grid-cols-2 sm:grid-cols-6 gap-2 mb-3">
                        <input type="text" name="name" value="{{ $filters['name'] }}" placeholder="{{ __('Nombre') }}"
                               class="sm:col-span-2 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
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
                        <select name="category_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                            <option value="">{{ __('Categoria (todas)') }}</option>
                            @foreach ($filterCategories as $c)
                                <option value="{{ $c->id }}" {{ (string) $filters['category_id'] === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <div class="sm:col-span-6 flex gap-2">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md">
                                {{ __('Filtrar') }}
                            </button>
                            @if ($filters['name'] !== '' || $filters['number'] !== '' || $filters['position'] !== '' || $filters['status'] !== 'all' || $filters['category_id'] !== null)
                                <a href="{{ route('teams.show', $team) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-md">
                                    {{ __('Limpiar filtros') }}
                                </a>
                                <span class="text-xs text-gray-500 self-center">{{ __('Filtros activos') }}</span>
                            @endif
                        </div>
                    </form>

                    {{-- Chips de categorias: clic = toggle del filtro de categoria --}}
                    @if ($teamCategories->count())
                        <div class="mb-3 flex flex-wrap gap-1.5">
                            @foreach ($teamCategories as $c)
                                @php
                                    $isActive = (string) $filters['category_id'] === (string) $c->id;
                                    $queryParams = request()->except(['category_id', 'page']);
                                    if (! $isActive && $c->id) {
                                        $queryParams['category_id'] = $c->id;
                                    }
                                @endphp
                                <a href="{{ route('teams.show', array_merge([$team], $queryParams)) }}"
                                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition
                                          {{ $isActive ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100' }}">
                                    {{ $c->name }}
                                    <span class="text-[10px] opacity-75 font-mono">{{ $categoryStats[$c->id]['athletes'] ?? 0 }}</span>
                                    @if ($c->active)
                                        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-white/70' : 'bg-green-500' }}"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Tabla del roster filtrado --}}
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-600 uppercase tracking-wider">N°</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('Nombre') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('Pos.') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('Categoria') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('B/L') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($athletes as $a)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $a->number ?? '—' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <a href="{{ route('athletes.show', $a) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $a->full_name }}</a>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            @if ($a->position)
                                                <span class="inline-block px-1.5 py-0.5 text-xs bg-gray-200 text-gray-700 rounded font-mono">{{ $a->position }}</span>
                                            @else —
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            @if ($a->active)
                                                <span class="inline-flex px-2 text-[10px] leading-4 font-semibold rounded-full bg-green-100 text-green-800">{{ __('Activo') }}</span>
                                            @else
                                                <span class="inline-flex px-2 text-[10px] leading-4 font-semibold rounded-full bg-gray-100 text-gray-800">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @if ($a->category)
                                                <a href="{{ route('categories.show', $a->category) }}" class="text-indigo-600 hover:text-indigo-800 text-xs">{{ $a->category->name }}</a>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-xs text-gray-700">{{ $a->bats }}/{{ $a->throws }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 text-sm">
                                            {{ __('No hay atletas que coincidan con el filtro.') }}
                                            @if ($filters['name'] !== '' || $filters['number'] !== '' || $filters['position'] !== '' || $filters['status'] !== 'all' || $filters['category_id'] !== null)
                                                <a href="{{ route('teams.show', $team) }}" class="text-indigo-600 hover:text-indigo-800 underline ms-2">{{ __('Limpiar filtros') }}</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- DISI-60: torneos en los que participa el equipo --}}
                @if ($team->tournaments->count())
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">
                            {{ __('Torneos en los que participa') }} ({{ $team->tournaments->count() }})
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($team->tournaments as $t)
                                <a href="{{ route('tournaments.show', $t) }}"
                                   class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-full border border-gray-200 text-sm">
                                    @if ($t->logo_url)
                                        <img src="{{ $t->logo_url }}" alt="" class="h-4 w-4 object-contain bg-white rounded p-0.5">
                                    @endif
                                    <span class="font-semibold text-gray-900">{{ $t->name }}</span>
                                    @if ($t->category)
                                        <span class="text-xs text-gray-500">({{ $t->category }})</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 pt-6 border-t border-gray-200 text-xs text-gray-500">
                    {{ __('Creado') }}: {{ $team->created_at->format('d/m/Y H:i') }} ·
                    {{ __('Actualizado') }}: {{ $team->updated_at->format('d/m/Y H:i') }}
                </div>

            </div>

            <form action="{{ route('teams.destroy', $team) }}" method="POST" class="mt-4 text-right"
                  onsubmit="return confirm('¿Eliminar el equipo «{{ $team->name }}»?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                    {{ __('Eliminar equipo') }}
                </button>
            </form>

        </div>
    </div>
</x-app-layout>
