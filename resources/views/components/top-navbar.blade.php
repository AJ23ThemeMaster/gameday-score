{{--
  WattVision: top navbar horizontal sobre el contenido principal.

  Estructura:
    [Buscador] ............ [Notificaciones dropdown]

  Visibilidad:
    - Desktop (>= md): visible y sticky top-0 (permanece al hacer scroll).
    - Mobile (< md): oculto. La top bar mobile (en layouts/app.blade.php)
      ya tiene la hamburguesa para abrir el drawer del sidebar y no
      compite con este navbar.

  Buscador:
    - Envia GET /search?q=...  (SearchController consulta los 9 modelos).
    - El placeholder enumera los tipos: juegos, ligas, categorias,
      torneos, equipos, atletas, anotadores, arbitros y estadios.
    - En resultados de busqueda (vista search.results), el campo se
      pre-rellena con la query actual.

  Notificaciones dropdown:
    - UI shell vacia por ahora (el backend de notificaciones esta
      pendiente por desarrollar; ver DESIGN.md / plan de notificaciones).
    - Boton con icono notifications; badge opcional con contador de no
      leidas (oculto si 0). Dropdown con lista placeholder + footer
      "Ver todas las notificaciones".
--}}

<nav class="bg-wv-bg border-b border-wv-border sticky top-0 z-20 hidden md:block">
    <div class="h-14 px-6 flex items-center gap-3">

        {{-- Buscador --}}
        <form method="GET" action="{{ route('search') }}" class="flex-1 max-w-2xl" role="search">
            <label for="top-navbar-search" class="sr-only">{{ __('Buscar') }}</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-wv-text-secondary text-[20px] pointer-events-none">search</span>
                <input
                    id="top-navbar-search"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="{{ __('Buscar juegos, ligas, atletas, equipos...') }}"
                    autocomplete="off"
                    class="w-full bg-wv-surface border border-wv-border rounded-card pl-10 pr-10 py-2 text-sm text-wv-text placeholder-wv-text-secondary focus:border-wv-accent focus:ring-2 focus:ring-wv-accent focus:ring-offset-2 focus:ring-offset-wv-bg focus:outline-none transition"
                >
                @if (request('q'))
                    <a href="{{ route('search') }}"
                       class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center w-7 h-7 rounded-md text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface-hover transition"
                       title="{{ __('Limpiar busqueda') }}">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </a>
                @endif
            </div>
        </form>

        <div class="ml-auto flex items-center gap-2">

            {{-- Dropdown de notificaciones (UI shell) --}}
            <div class="relative" x-data="{ notifOpen: false }">
                <button
                    type="button"
                    @click="notifOpen = !notifOpen"
                    @click.outside="notifOpen = false"
                    :class="notifOpen ? 'bg-wv-surface border-wv-accent text-wv-accent' : 'border-wv-border text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface hover:border-wv-border-strong'"
                    class="relative inline-flex items-center justify-center w-10 h-10 rounded-card border transition"
                    :aria-expanded="notifOpen ? 'true' : 'false'"
                    aria-haspopup="true"
                    aria-label="{{ __('Notificaciones') }}"
                >
                    <span class="material-symbols-outlined text-[20px]">notifications</span>

                    {{-- Badge de no leidas. hidden por ahora (funcionalidad pendiente). --}}
                    <span x-show="false"
                          class="absolute top-1.5 right-1.5 inline-flex items-center justify-center min-w-[16px] h-4 px-1 text-[10px] font-bold rounded-full bg-wv-alert text-wv-text-on-alert">
                    </span>
                </button>

                {{-- Panel dropdown --}}
                <div
                    x-show="notifOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    class="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-wv-surface border border-wv-border rounded-card shadow-2xl overflow-hidden"
                    role="menu"
                    x-cloak
                >
                    {{-- Header --}}
                    <div class="px-4 py-3 border-b border-wv-border flex items-center justify-between">
                        <h3 class="text-sm font-bold text-wv-text">{{ __('Notificaciones') }}</h3>
                        <span class="text-xs text-wv-text-secondary">{{ __('Recientes') }}</span>
                    </div>

                    {{-- Lista de notificaciones (placeholder) --}}
                    <div class="max-h-96 overflow-y-auto nav-scroll">
                        <div class="p-8 text-center">
                            <span class="material-symbols-outlined text-wv-text-secondary text-[40px]">notifications_off</span>
                            <p class="mt-2 text-sm font-medium text-wv-text">{{ __('Sin notificaciones') }}</p>
                            <p class="mt-1 text-xs text-wv-text-secondary leading-relaxed">
                                {{ __('Aqui veras los avisos generados para tu usuario (juegos asignados, cambios de estado, etc.).') }}<br>
                                <span class="text-wv-accent">{{ __('Funcionalidad pendiente por desarrollar.') }}</span>
                            </p>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-4 py-2.5 border-t border-wv-border bg-wv-bg flex items-center justify-between">
                        <button type="button"
                                disabled
                                class="text-xs text-wv-text-secondary opacity-50 cursor-not-allowed">
                            {{ __('Marcar todas como leidas') }}
                        </button>
                        <button type="button"
                                disabled
                                class="text-xs font-semibold text-wv-text-secondary opacity-50 cursor-not-allowed">
                            {{ __('Ver todas') }} →
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>