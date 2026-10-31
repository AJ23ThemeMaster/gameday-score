<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-h-wv text-wv-text leading-tight flex items-center gap-3">
                @if ($athlete->photoUrl)
                    <img src="{{ $athlete->photoUrl }}" class="h-10 w-10 rounded-full object-cover bg-wv-surface border border-wv-border p-0.5">
                @endif
                {{ __('Atleta') }}: {{ $athlete->full_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('athletes.index') }}" class="text-sm text-wv-text-secondary hover:text-wv-text">{{ __('Listado') }}</a>
                <a href="{{ route('athletes.edit', $athlete) }}" class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-card">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    @if ($athlete->active)
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-success/15 text-wv-success border border-wv-success/40">{{ __('Activo') }}</span>
                    @else
                        <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-wv-surface-hover text-wv-text-secondary border border-wv-border">{{ __('Inactivo') }}</span>
                    @endif
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Cédula') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $athlete->document_id ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Fecha de nacimiento') }}</dt>
                        <dd class="text-base font-medium text-wv-text">{{ $athlete->birth_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Equipo') }}</dt>
                        <dd class="text-base font-medium text-wv-text">
                            @if ($athlete->team)
                                <a href="{{ route('teams.show', $athlete->team) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $athlete->team->name }}</a>
                                @if ($athlete->team->league)
                                    <span class="text-wv-text-secondary mx-1">·</span>
                                    <a href="{{ route('leagues.show', $athlete->team->league) }}" class="text-sm text-wv-text-secondary hover:text-wv-accent">{{ $athlete->team->league->name }}</a>
                                @endif
                            @else —
                            @endif
                        </dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Categorías') }}</dt>
                        <dd class="text-base font-medium text-wv-text">
                            {{-- DISI-N: atletas pueden pertenecer a 1 o 2
                                 categorias del mismo equipo. Listamos todas
                                 las de la pivot (athlete_category). --}}
                            @if ($categoryList->isNotEmpty())
                                <ul class="space-y-1">
                                    @foreach ($categoryList as $cat)
                                        <li>
                                            <a href="{{ route('categories.show', $cat) }}" class="text-wv-accent hover:text-wv-accent-hover">{{ $cat->name }}</a>
                                            @if ($cat->team)
                                                <span class="text-xs text-wv-text-secondary">· {{ $cat->team->short_name ?? $cat->team->name }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else —
                            @endif
                        </dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Número') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $athlete->number ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Posición') }}</dt>
                        <dd class="font-mono text-2xl font-semibold text-wv-text">{{ $athlete->position ?? '—' }}</dd>
                    </div>
                    <div class="bg-wv-bg border border-wv-border rounded-card p-3">
                        <dt class="text-wv-text-secondary text-xs uppercase">{{ __('Batea / Lanza') }}</dt>
                        <dd class="text-lg font-mono font-semibold text-wv-text">{{ $athlete->bats }} / {{ $athlete->throws }}</dd>
                    </div>
                </dl>
                <div class="mt-6 pt-6 border-t border-wv-border text-xs text-wv-text-secondary">
                    {{ __('Creado') }}: {{ $athlete->created_at->format('d/m/Y H:i') }} · {{ __('Actualizado') }}: {{ $athlete->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- DISI-63: documento de identidad --}}
            <div class="mt-6 bg-wv-surface border border-wv-border rounded-card p-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-bold text-wv-text flex items-center gap-2">
                        <span class="inline-block w-2 h-2 bg-wv-success rounded-full"></span>
                        {{ __('Documento de identidad') }}
                    </h3>
                    <a href="{{ route('athletes.edit', $athlete) }}" class="text-xs text-wv-accent hover:text-wv-accent-hover underline">
                        {{ __('Reemplazar') }}
                    </a>
                </div>
                @if (!empty($athlete->document_file_path) && Storage::disk('public')->exists($athlete->document_file_path))
                    <div class="flex items-start gap-4">
                        @if ($athlete->documentIsImage)
                            <a href="{{ $athlete->documentUrl }}" target="_blank" class="block">
                                <img src="{{ $athlete->documentUrl }}" alt="Documento de identidad"
                                     class="h-32 w-44 object-cover bg-wv-surface rounded border border-wv-border p-1 hover:opacity-80">
                            </a>
                        @else
                            <a href="{{ $athlete->documentUrl }}" target="_blank"
                               class="flex flex-col items-center justify-center h-32 w-44 bg-wv-alert-bg rounded border border-wv-alert/40 text-wv-alert hover:bg-wv-alert hover:text-wv-text-on-alert">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                <span class="mt-1 text-xs font-semibold">PDF</span>
                            </a>
                        @endif
                        <div class="flex-1">
                            <p class="text-sm text-wv-text-secondary">
                                @if ($athlete->documentIsImage)
                                    {{ __('Imagen del documento cargada correctamente.') }}
                                @else
                                    {{ __('PDF del documento cargado correctamente.') }}
                                @endif
                            </p>
                            <p class="text-xs text-wv-text-secondary mt-1 font-mono break-all">{{ basename($athlete->document_file_path) }}</p>
                            <div class="mt-3 flex gap-3">
                                <a href="{{ $athlete->documentUrl }}" target="_blank"
                                   class="inline-flex items-center px-3 py-1.5 bg-wv-accent hover:bg-wv-accent-hover text-wv-text-on-accent text-xs font-semibold rounded-md">
                                    {{ $athlete->documentIsImage ? __('Ver imagen') : __('Ver PDF') }}
                                </a>
                                <a href="{{ $athlete->documentUrl }}" download
                                   class="inline-flex items-center px-3 py-1.5 bg-wv-surface border border-wv-border hover:bg-wv-surface-hover text-wv-text text-xs font-semibold rounded-md">
                                    {{ __('Descargar') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 text-sm text-wv-text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-wv-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        <span>{{ __('No hay documento cargado. Puedes subir la cédula o acta de nacimiento al editar el atleta.') }}</span>
                    </div>
                @endif
            </div>

            {{-- DISI-62: stats carrera --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-wv-surface border border-wv-border rounded-card p-5">
                    <h3 class="text-sm font-bold text-wv-text mb-3 flex items-center gap-2">
                        <span class="inline-block w-2 h-2 bg-wv-accent rounded-full"></span>
                        {{ __('Estadísticas como bateador') }}
                    </h3>
                    @if ($careerBatting['at_bats'] > 0 || $careerBatting['hits'] > 0)
                        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                            @foreach (['at_bats' => 'AB', 'hits' => 'H', 'avg' => 'AVG', 'walks' => 'BB', 'strikeouts' => 'K', 'rbi' => 'RBI', 'hr' => 'HR'] as $key => $label)
                                <div class="bg-wv-bg border border-wv-border rounded p-2">
                                    <dt class="text-wv-text-secondary text-[10px] uppercase">{{ $label }}</dt>
                                    <dd class="font-mono text-xl font-bold text-wv-text">{{ $key === 'avg' ? number_format($careerBatting[$key], 3, '.', '') : $careerBatting[$key] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-wv-text-secondary italic">{{ __('Aún no tiene turnos al bate registrados.') }}</p>
                    @endif

                    @if ($statsByCategory->count() > 1)
                        <div class="mt-4 pt-3 border-t border-wv-border">
                            <p class="text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider mb-2">
                                {{ __('Desglose por categoría') }}
                            </p>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-wv-text-secondary uppercase">
                                        <th class="text-left font-semibold pb-1">{{ __('Categoría') }}</th>
                                        <th class="text-center font-semibold pb-1">AB</th>
                                        <th class="text-center font-semibold pb-1">H</th>
                                        <th class="text-center font-semibold pb-1">AVG</th>
                                        <th class="text-center font-semibold pb-1">BB</th>
                                        <th class="text-center font-semibold pb-1">K</th>
                                        <th class="text-center font-semibold pb-1">RBI</th>
                                        <th class="text-center font-semibold pb-1">HR</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-wv-border">
                                    @foreach ($statsByCategory as $catRow)
                                        @php $cb = $catRow['batting']; @endphp
                                        <tr>
                                            <td class="py-1.5 text-wv-text">
                                                @if ($catRow['category'])
                                                    <a href="{{ route('categories.show', $catRow['category']) }}" class="hover:text-wv-accent hover:text-wv-accent-hover">{{ $catRow['category']->name }}</a>
                                                @else
                                                    <span class="text-wv-text-secondary italic">{{ __('Sin categoría') }}</span>
                                                @endif
                                            </td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cb['at_bats'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cb['hits'] }}</td>
                                            <td class="py-1.5 text-center font-mono font-semibold {{ $cb['avg'] >= 0.3 ? 'text-wv-success' : 'text-wv-text' }}">{{ number_format($cb['avg'], 3, '.', '') }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cb['walks'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cb['strikeouts'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cb['rbi'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cb['hr'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="bg-wv-surface border border-wv-border rounded-card p-5">
                    <h3 class="text-sm font-bold text-wv-text mb-3 flex items-center gap-2">
                        <span class="inline-block w-2 h-2 bg-wv-accent rounded-full"></span>
                        {{ __('Estadísticas como lanzador') }}
                    </h3>
                    @if ($careerPitching['pitches'] > 0 || $careerPitching['strikeouts'] > 0)
                        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                            <div class="bg-wv-bg border border-wv-border rounded p-2">
                                <dt class="text-wv-text-secondary text-[10px] uppercase">{{ __('lanz.') }}</dt>
                                <dd class="font-mono text-xl font-bold text-wv-text">{{ $careerPitching['pitches'] }}</dd>
                            </div>
                            <div class="bg-wv-bg border border-wv-border rounded p-2">
                                <dt class="text-wv-text-secondary text-[10px] uppercase">S/B</dt>
                                <dd class="font-mono text-sm font-bold text-wv-text">{{ $careerPitching['strikes'] }}S / {{ $careerPitching['balls'] }}B</dd>
                            </div>
                            @foreach (['strikeouts' => 'K', 'hits' => 'H', 'walks' => 'BB'] as $key => $label)
                                <div class="bg-wv-bg border border-wv-border rounded p-2">
                                    <dt class="text-wv-text-secondary text-[10px] uppercase">{{ $label }}</dt>
                                    <dd class="font-mono text-xl font-bold text-wv-text">{{ $careerPitching[$key] }}</dd>
                                </div>
                            @endforeach
                            <div class="bg-wv-bg border border-wv-border rounded p-2">
                                <dt class="text-wv-text-secondary text-[10px] uppercase" title="Jonrones permitidos">{{ __('HR perm.') }}</dt>
                                <dd class="font-mono text-xl font-bold text-wv-text">{{ $careerPitching['hr_allowed'] }}</dd>
                            </div>
                            <div class="bg-wv-bg border border-wv-border rounded p-2">
                                <dt class="text-wv-text-secondary text-[10px] uppercase" title="RBI permitidos">{{ __('RBI perm.') }}</dt>
                                <dd class="font-mono text-xl font-bold text-wv-text">{{ $careerPitching['rbi_allowed'] }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-wv-text-secondary italic">{{ __('Aún no tiene lanzamientos registrados.') }}</p>
                    @endif

                    @if ($statsByCategory->count() > 1)
                        <div class="mt-4 pt-3 border-t border-wv-border">
                            <p class="text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider mb-2">
                                {{ __('Desglose por categoría') }}
                            </p>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-wv-text-secondary uppercase">
                                        <th class="text-left font-semibold pb-1">{{ __('Categoría') }}</th>
                                        <th class="text-center font-semibold pb-1">{{ __('lanz.') }}</th>
                                        <th class="text-center font-semibold pb-1">S</th>
                                        <th class="text-center font-semibold pb-1">B</th>
                                        <th class="text-center font-semibold pb-1">K</th>
                                        <th class="text-center font-semibold pb-1">H</th>
                                        <th class="text-center font-semibold pb-1">BB</th>
                                        <th class="text-center font-semibold pb-1">HR</th>
                                        <th class="text-center font-semibold pb-1">RBI</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-wv-border">
                                    @foreach ($statsByCategory as $catRow)
                                        @php $cp = $catRow['pitching']; @endphp
                                        <tr>
                                            <td class="py-1.5 text-wv-text">
                                                @if ($catRow['category'])
                                                    <a href="{{ route('categories.show', $catRow['category']) }}" class="hover:text-wv-accent hover:text-wv-accent-hover">{{ $catRow['category']->name }}</a>
                                                @else
                                                    <span class="text-wv-text-secondary italic">{{ __('Sin categoría') }}</span>
                                                @endif
                                            </td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cp['pitches'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cp['strikes'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cp['balls'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cp['strikeouts'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cp['hits'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cp['walks'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cp['hr_allowed'] }}</td>
                                            <td class="py-1.5 text-center font-mono text-wv-text">{{ $cp['rbi_allowed'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- DISI-N: stats por categoria (una fila por categoria, lado a lado).
                 Es el desglose principal pedido: un atleta en Pre-infantil +
                 Infantil ve sus numeros de cada categoria por separado. --}}
            <div class="mt-6 bg-wv-surface border border-wv-border rounded-card">
                <div class="p-5 border-b border-wv-border flex justify-between items-center">
                    <h3 class="text-base font-bold text-wv-text">{{ __('Estadísticas por categoría') }}</h3>
                    <span class="text-sm text-wv-text-secondary">
                        {{ $statsByCategory->count() }} {{ \Illuminate\Support\Str::plural('categoría', $statsByCategory->count()) }}
                    </span>
                </div>
                @if ($statsByCategory->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border text-sm">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Categoría') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider" title="Juegos jugados en la categoría">{{ __('JJ') }}</th>
                                    <th colspan="7" class="px-3 py-2 text-center text-[10px] font-bold text-wv-accent uppercase tracking-wider bg-wv-accent-soft">{{ __('Bateo') }}</th>
                                    <th colspan="7" class="px-3 py-2 text-center text-[10px] font-bold text-wv-text uppercase tracking-wider bg-wv-surface-hover">{{ __('Pitcheo') }}</th>
                                </tr>
                                <tr class="border-t border-wv-border">
                                    <th></th><th></th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">AB</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">H</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">AVG</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">BB</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">K</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">RBI</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">HR</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">Lanz.</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">S</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">B</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">K</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">H</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">BB</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">HR</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">RBI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($statsByCategory as $row)
                                    @php $b = $row['batting']; $p = $row['pitching']; @endphp
                                    <tr class="hover:bg-wv-surface-hover">
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @if ($row['category'])
                                                <a href="{{ route('categories.show', $row['category']) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $row['category']->name }}</a>
                                                @if (! $row['in_roster'])
                                                    <span class="ml-1 text-[10px] text-wv-text-secondary italic">({{ __('fuera de roster actual') }})</span>
                                                @endif
                                            @else
                                                <span class="text-wv-text-secondary italic">{{ __('Sin categoría') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $row['games_count'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['at_bats'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['hits'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono font-semibold {{ $b['avg'] >= 0.3 ? 'text-wv-success' : 'text-wv-text' }}">{{ number_format($b['avg'], 3, '.', '') }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['walks'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['strikeouts'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['rbi'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['hr'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['pitches'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['strikes'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['balls'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['strikeouts'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['hits'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['walks'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['hr_allowed'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['rbi_allowed'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="px-5 py-2 text-[11px] text-wv-text-secondary italic border-t border-wv-border">
                        {{ __('Cada jugada se atribuye a la categoría del juego en el que ocurrió. AVG = H/AB de la categoría. JJ = juegos como bateador o pitcher en esa categoría.') }}
                    </p>
                @else
                    <div class="p-8 text-center text-wv-text-secondary text-sm">{{ __('Aún no hay jugadas registradas para agrupar por categoría.') }}</div>
                @endif
            </div>

            {{-- DISI-79: stats por torneo. DISI-N: cada fila es (torneo, categoría)
                 para que el desglose por torneo se pueda filtrar más aún por
                 categoría (ej. un torneo con Pre-infantil y Infantil). --}}
            <div class="mt-6 bg-wv-surface border border-wv-border rounded-card">
                <div class="p-5 border-b border-wv-border flex justify-between items-center">
                    <h3 class="text-base font-bold text-wv-text">{{ __('Estadísticas por torneo') }}</h3>
                    <span class="text-sm text-wv-text-secondary">
                        {{ $perTournamentByCategory->count() }} {{ \Illuminate\Support\Str::plural('fila', $perTournamentByCategory->count()) }}
                    </span>
                </div>
                @if ($perTournamentByCategory->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border text-sm">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Torneo') }}</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Categoría') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider" title="Juegos jugados en el torneo">{{ __('JJ') }}</th>
                                    <th colspan="7" class="px-3 py-2 text-center text-[10px] font-bold text-wv-accent uppercase tracking-wider bg-wv-accent-soft">{{ __('Bateo') }}</th>
                                    <th colspan="7" class="px-3 py-2 text-center text-[10px] font-bold text-wv-text uppercase tracking-wider bg-wv-surface-hover">{{ __('Pitcheo') }}</th>
                                </tr>
                                <tr class="border-t border-wv-border">
                                    <th></th><th></th><th></th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">AB</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">H</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">AVG</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">BB</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">K</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">RBI</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">HR</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">Lanz.</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">S</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">B</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">K</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">H</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">BB</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">HR</th>
                                    <th class="px-3 py-1 text-center text-[10px] font-semibold text-wv-text-secondary uppercase">RBI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($perTournamentByCategory as $row)
                                    @php $b = $row['batting']; $p = $row['pitching']; @endphp
                                    <tr class="hover:bg-wv-surface-hover">
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @if ($row['tournament'])
                                                <a href="{{ route('tournaments.show', $row['tournament']) }}" class="text-wv-accent hover:text-wv-accent-hover font-medium">{{ $row['tournament']->name }}</a>
                                            @else
                                                <span class="text-wv-text-secondary italic">{{ __('Sin torneo') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @if ($row['category'])
                                                <a href="{{ route('categories.show', $row['category']) }}" class="text-wv-text hover:text-wv-accent">{{ $row['category']->name }}</a>
                                            @else
                                                <span class="text-wv-text-secondary italic">{{ __('Sin categoría') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $row['games_count'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['at_bats'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['hits'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono font-semibold {{ $b['avg'] >= 0.3 ? 'text-wv-success' : 'text-wv-text' }}">{{ number_format($b['avg'], 3, '.', '') }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['walks'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['strikeouts'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['rbi'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['hr'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['pitches'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['strikes'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['balls'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['strikeouts'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['hits'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['walks'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['hr_allowed'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['rbi_allowed'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="px-5 py-2 text-[11px] text-wv-text-secondary italic border-t border-wv-border">
                        {{ __('AVG = H/AB de la fila (torneo + categoría). JJ = juegos jugados como bateador o pitcher. Los juegos sin torneo asignado (amistosos) se agrupan en "Sin torneo".') }}
                    </p>
                @else
                    <div class="p-8 text-center text-wv-text-secondary text-sm">{{ __('Aún no hay jugadas registradas para agrupar por torneo.') }}</div>
                @endif
            </div>

            {{-- DISI-62: stats per game --}}
            <div class="mt-6 bg-wv-surface border border-wv-border rounded-card">
                <div class="p-5 border-b border-wv-border flex justify-between items-center">
                    <h3 class="text-base font-bold text-wv-text">{{ __('Estadísticas por juego') }}</h3>
                    <span class="text-sm text-wv-text-secondary">{{ $games->count() }} {{ \Illuminate\Support\Str::plural('juego', $games->count()) }}</span>
                </div>
                @if ($perGame)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-wv-border text-sm">
                            <thead class="bg-wv-surface-hover">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Fecha') }}</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-wv-text-secondary uppercase tracking-wider">{{ __('Oponente') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-accent uppercase tracking-wider" colspan="7">{{ __('Bateo') }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-wv-text uppercase tracking-wider" colspan="7">{{ __('Pitcheo') }}</th>
                                </tr>
                                <tr class="bg-wv-surface-hover">
                                    <th class="px-3 py-1"></th>
                                    <th class="px-3 py-1"></th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">AB</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">H</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">AVG</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">BB</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">K</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">RBI</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">HR</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">{{ __('lanz.') }}</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">S/B</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">K</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">H</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">BB</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">HR</th>
                                    <th class="px-3 py-1 text-center text-[10px] text-wv-text-secondary">RBI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wv-border">
                                @foreach ($perGame as $row)
                                    @php
                                        $b = $row['batting'];
                                        $p = $row['pitching'];
                                        $g = $row['game'];
                                        $opponent = $g->home_team_id === $athlete->team_id ? $g->awayTeam : $g->homeTeam;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap text-wv-text-secondary">{{ $g->scheduled_at?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-wv-text">
                                            <a href="{{ route('games.scoreboard', $g) }}" class="hover:text-wv-accent">
                                                @if ($g->home_team_id === $athlete->team_id)
                                                    <span class="text-wv-text-secondary text-xs">vs </span>{{ $opponent->short_name ?? $opponent->name }}
                                                @else
                                                    <span class="text-wv-text-secondary text-xs">@ </span>{{ $opponent->short_name ?? $opponent->name }}
                                                @endif
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['at_bats'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['hits'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['at_bats'] > 0 ? number_format($b['hits'] / max(1, $b['at_bats']), 3, '.', '') : '.000' }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['walks'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['strikeouts'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['rbi'] ?? 0 }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $b['hr'] ?? 0 }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['pitches'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['strikes'] }}S/{{ $p['balls'] }}B</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['strikeouts'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['hits'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['walks'] }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['hr_allowed'] ?? 0 }}</td>
                                        <td class="px-3 py-2 text-center font-mono text-wv-text">{{ $p['rbi_allowed'] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="px-5 py-2 text-[11px] text-wv-text-secondary italic border-t border-wv-border">
                        {{ __('AVG por juego se calcula como H/AB del propio juego. S/B: strikes/balls lanzados. HR/RBI en pitcheo son los permitidos por el lanzador.') }}
                    </p>
                @else
                    <p class="p-6 text-wv-text-secondary text-sm">{{ __('Este atleta aún no ha participado en juegos registrados.') }}</p>
                @endif
            </div>

            <form action="{{ route('athletes.destroy', $athlete) }}" method="POST" class="mt-4 text-right" data-confirm="'¿Eliminar a «{{ $athlete->full_name }}»?'" data-confirm-danger="true" data-loader>
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-wv-alert hover:text-wv-alert-hover">{{ __('Eliminar atleta') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>