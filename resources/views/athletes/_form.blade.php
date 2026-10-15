<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <x-input-label for="first_name" :value="__('Nombre')" />
        <x-text-input id="first_name" name="first_name" type="text" class="block mt-1 w-full"
                      :value="old('first_name', $athlete->first_name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="last_name" :value="__('Apellido')" />
        <x-text-input id="last_name" name="last_name" type="text" class="block mt-1 w-full"
                      :value="old('last_name', $athlete->last_name ?? '')" required />
        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="document_id" :value="__('Cédula / Documento')" />
        <x-text-input id="document_id" name="document_id" type="text" maxlength="30"
                      class="block mt-1 w-full" :value="old('document_id', $athlete->document_id ?? '')" />
        <x-input-error :messages="$errors->get('document_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="birth_date" :value="__('Fecha de nacimiento')" />
        <x-text-input id="birth_date" name="birth_date" type="date" class="block mt-1 w-full"
                      :value="old('birth_date', isset($athlete->birth_date) ? $athlete->birth_date->format('Y-m-d') : '')" />
        <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="team_id" :value="__('Equipo actual')" />
        {{-- DISI-XXX + DISI-delegado: gestor y delegado solo pueden asignar
             atletas a SU equipo asociado (delegado ademas queda restringido
             a SU categoria). El select se pre-selecciona con ese unico
             equipo y se deshabilita. Admin sigue viendo la lista completa. --}}
        @auth
            @if (auth()->user()->isGestor() || auth()->user()->isDelegado())
                <select id="team_id" name="team_id" disabled
                        class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text rounded-md shadow-sm opacity-70 cursor-not-allowed">
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" selected>
                            {{ $team->name }}@if ($team->league) ({{ $team->league->short_name ?? $team->league->name }})@endif
                        </option>
                    @endforeach
                </select>
                {{-- Campo hidden para que el POST envie el team_id aunque el
                     select este disabled (los campos disabled no se envian). --}}
                <input type="hidden" name="team_id" value="{{ $teams->first()?->id }}">
                @if (auth()->user()->isDelegado())
                    <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Como delegado, los atletas que edites quedaran asignados automaticamente a tu equipo y a tu categoria.') }}</p>
                @else
                    <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Como gestor, los atletas que crees quedaran asignados automaticamente a tu equipo.') }}</p>
                @endif
            @else
                <select id="team_id" name="team_id" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
                    <option value="">— {{ __('Sin equipo') }} —</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" {{ (string) old('team_id', $athlete->team_id ?? request('team_id', '')) === (string) $team->id ? 'selected' : '' }}>
                            {{ $team->name }}@if ($team->league) ({{ $team->league->short_name ?? $team->league->name }})@endif
                        </option>
                    @endforeach
                </select>
            @endif
        @endauth
        <x-input-error :messages="$errors->get('team_id')" class="mt-2" />
    </div>

    {{-- DISI-N: multi-select de categorias (max 2 del mismo equipo).
         Refactor a checkboxes nativos + JS vanilla (sin Alpine component)
         por dos motivos:
           1. El layout principal NO tiene @stack('scripts'), asi que un
              componente Alpine registrado via window.* desde un push se
              evaluaba sin estar disponible -> Alpine.data('xxx') fallaba
              silenciosamente y los checkboxes quedaban "muertos".
           2. x-data anidados creaban scopes independientes y las
              referencias cruzadas (selected/toggle) no funcionaban.
         Ahora: checkboxes nativos con `name="categories[]"` (Blade serializa
         como array en POST), el estado checked se hidrata con PHP puro desde
         $selectedCategoryIds / old('categories'), y el limite de 2 lo
         enforza JS vanilla al change. La regla definitiva vive en
         Athlete::syncCategories() server-side (defense in depth). --}}
    <div>
        <x-input-label for="categories" :value="__('Categorías (máx. 2, mismo equipo)')" />
        @auth
            @if (auth()->user()->isDelegado())
                {{-- Delegado: solo su categoria (la pivot se llena con un unico ID). --}}
                <select id="categories" name="categories[]" disabled
                        class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text rounded-md shadow-sm opacity-70 cursor-not-allowed">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" selected>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="categories[]" value="{{ $categories->first()?->id }}">
                <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Como delegado, solo puedes asignar atletas a tu categoría.') }}</p>
            @else
                {{-- Admin / gestor: grupo de checkboxes nativos con limite JS a 2. --}}
                <div id="athlete-categories-group"
                     data-max="2"
                     class="mt-1 border border-wv-border bg-wv-surface rounded-md shadow-sm p-3 space-y-2">
                    @php
                        // Normalizar $selectedCategoryIds a array de ints para
                        // que in_array(... true) funcione incluso si viniera
                        // con strings (ej. del roundtrip old()).
                        $preselected = array_map('intval', (array) old('categories', $selectedCategoryIds ?? []));
                    @endphp
                    @forelse ($categories as $cat)
                        <label class="flex items-center gap-2 text-sm text-wv-text"
                               data-cat-checkbox
                               data-cat-id="{{ (int) $cat->id }}">
                            <input type="checkbox"
                                   class="athlete-cat-cb rounded border-wv-border bg-wv-bg text-wv-accent focus:ring-wv-accent"
                                   name="categories[]"
                                   value="{{ (int) $cat->id }}"
                                   {{ in_array((int) $cat->id, $preselected, true) ? 'checked' : '' }}>
                            <span>
                                {{ $cat->name }}
                                @if ($cat->team)
                                    <span class="text-xs text-wv-text-secondary">({{ $cat->team->short_name ?? $cat->team->name }})</span>
                                @else
                                    <span class="text-xs text-wv-text-secondary">({{ __('Sin equipo') }})</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <p class="text-sm text-wv-text-secondary italic">{{ __('No hay categorías registradas.') }}</p>
                    @endforelse
                </div>
                <p class="mt-1 text-xs text-wv-text-secondary">
                    {{ __('Selecciona hasta 2 categorías del mismo equipo del atleta.') }}
                    <span id="athlete-cats-count" class="font-mono"></span>
                </p>
                <p id="athlete-cats-warning"
                   class="mt-1 text-xs text-wv-alert hidden"
                   role="alert">
                    {{ __('Has alcanzado el máximo de 2 categorías. Desmarca una para cambiar.') }}
                </p>
            @endif
        @endauth
        <x-input-error :messages="$errors->get('categories')" class="mt-2" />
        @foreach ($errors->get('categories.*') as $msg)
            <p class="text-sm text-wv-alert mt-1">{{ $msg }}</p>
        @endforeach
    </div>

    <div>
        <x-input-label for="number" :value="__('Número (camiseta)')" />
        <x-text-input id="number" name="number" type="number" min="0" max="99"
                      class="block mt-1 w-full" :value="old('number', $athlete->number ?? '')" />
        <x-input-error :messages="$errors->get('number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="position" :value="__('Posición')" />
        <select id="position" name="position" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            <option value="">— {{ __('Sin posición') }} —</option>
            @foreach (['P' => 'P - Pitcher', 'C' => 'C - Catcher', '1B' => '1B', '2B' => '2B', '3B' => '3B', 'SS' => 'SS', 'LF' => 'LF - Left Field', 'CF' => 'CF - Center Field', 'RF' => 'RF - Right Field', 'DH' => 'DH - Designated Hitter'] as $key => $label)
                <option value="{{ $key }}" {{ old('position', $athlete->position ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('position')" class="mt-2" />
    </div>

    <div class="grid grid-cols-2 gap-2">
        <div>
            <x-input-label for="bats" :value="__('Bateo')" />
            <select id="bats" name="bats" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
                <option value="R" {{ old('bats', $athlete->bats ?? 'R') === 'R' ? 'selected' : '' }}>{{ __('Derecha') }} (R)</option>
                <option value="L" {{ old('bats', $athlete->bats ?? 'R') === 'L' ? 'selected' : '' }}>{{ __('Izquierda') }} (L)</option>
                <option value="S" {{ old('bats', $athlete->bats ?? 'R') === 'S' ? 'selected' : '' }}>{{ __('Switch') }} (S)</option>
            </select>
        </div>
        <div>
            <x-input-label for="throws" :value="__('Lanzamiento')" />
            <select id="throws" name="throws" class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
                <option value="R" {{ old('throws', $athlete->throws ?? 'R') === 'R' ? 'selected' : '' }}>{{ __('Derecha') }} (R)</option>
                <option value="L" {{ old('throws', $athlete->throws ?? 'R') === 'L' ? 'selected' : '' }}>{{ __('Izquierda') }} (L)</option>
            </select>
        </div>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="photo" :value="__('Foto del atleta')" />
        @if (! empty($athlete?->photo_path) && Storage::disk('public')->exists($athlete->photo_path))
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ $athlete->photoUrl }}" alt="Foto actual"
                     class="h-20 w-20 object-cover bg-wv-surface rounded-full border border-wv-border p-1">
                <label class="flex items-center text-sm text-wv-alert">
                    <input type="checkbox" name="remove_photo" value="1"
                           class="rounded border-wv-border bg-wv-surface text-wv-alert focus:ring-wv-alert mr-2">
                    <span class="ms-2">{{ __('Eliminar foto actual') }}</span>
                </label>
            </div>
        @endif
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
               class="mt-2 block w-full text-sm text-wv-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-wv-accent-soft file:text-wv-accent hover:file:bg-wv-accent">
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Formatos: JPG, PNG o WEBP. Tamaño máximo: 2 MB.') }}</p>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="document_file" :value="__('Documento de identidad (cédula o acta de nacimiento)')" />
        @if (! empty($athlete?->document_file_path) && Storage::disk('public')->exists($athlete->document_file_path))
            <div class="mt-2 flex items-center gap-4">
                @if ($athlete->documentIsImage)
                    <img src="{{ $athlete->documentUrl }}" alt="Documento actual"
                         class="h-20 w-28 object-cover bg-wv-surface rounded border border-wv-border p-1">
                @else
                    <a href="{{ $athlete->documentUrl }}" target="_blank"
                       class="inline-flex items-center gap-2 text-sm text-wv-accent hover:text-wv-accent-hover underline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        {{ __('Ver documento actual') }}
                    </a>
                @endif
                <label class="flex items-center text-sm text-wv-alert">
                    <input type="checkbox" name="remove_document" value="1"
                           class="rounded border-wv-border bg-wv-surface text-wv-alert focus:ring-wv-alert mr-2">
                    <span class="ms-2">{{ __('Eliminar documento actual') }}</span>
                </label>
            </div>
        @endif
        <input id="document_file" name="document_file" type="file" accept="image/jpeg,image/png,image/webp,application/pdf"
               class="mt-2 block w-full text-sm text-wv-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-wv-accent-soft file:text-wv-accent hover:file:bg-wv-accent">
        <x-input-error :messages="$errors->get('document_file')" class="mt-2" />
        <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Formatos: JPG, PNG, WEBP o PDF. Tamaño máximo: 5 MB.') }}</p>
    </div>

    <div class="md:col-span-2 flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $athlete->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Atleta activo (visible para asignar a juegos)') }}
        </label>
    </div>

</div>

{{-- Script inline (no @push porque el layout principal no tiene @stack('scripts')):
     limita la seleccion del grupo de categorias a `data-max` checkboxes,
     muestra el contador "N/2" y un warning cuando se llega al maximo.
     La regla DEFINITIVA vive server-side en Athlete::syncCategories(); este
     script solo es UX (que el usuario no marque mas de 2 visuales).

     NOTA Blade: NO usar directivas Blade (como @@once o @@endonce) dentro
     de <script>. Blade parsea @@xxx dentro de <script> como si fueran
     directivas; esto rompe el template. Ademas, en este caso usamos
     checkboxes nativos y el JS se ejecuta una sola vez por inclusion
     del form, asi que el @@once wrapper no aporta. --}}
<script>
(function () {
    var group = document.getElementById('athlete-categories-group');
    if (!group) return;
    var max = parseInt(group.getAttribute('data-max') || '2', 10);
    var cbs = group.querySelectorAll('.athlete-cat-cb');
    var counter = document.getElementById('athlete-cats-count');
    var warning = document.getElementById('athlete-cats-warning');

    function updateCounter() {
        var checked = group.querySelectorAll('.athlete-cat-cb:checked').length;
        if (counter) {
            counter.textContent = checked > 0 ? ' (' + checked + '/' + max + ')' : '';
        }
    }

    function syncDisabledState() {
        var checked = Array.from(group.querySelectorAll('.athlete-cat-cb:checked'));
        var reachedLimit = checked.length >= max;
        if (warning) {
            if (reachedLimit) {
                warning.classList.remove('hidden');
            } else {
                warning.classList.add('hidden');
            }
        }
        cbs.forEach(function (cb) {
            // Un checkbox ya checked no se bloquea: el usuario debe poder
            // desmarcarlo para liberar el cupo. Los NO checked se bloquean
            // cuando ya hay `max` marcados.
            if (cb.checked) {
                cb.disabled = false;
            } else {
                cb.disabled = reachedLimit;
            }
        });
    }

    group.addEventListener('change', function (event) {
        if (event.target && event.target.classList.contains('athlete-cat-cb')) {
            syncDisabledState();
            updateCounter();
        }
    });

    syncDisabledState();
    updateCounter();
})();
</script>
