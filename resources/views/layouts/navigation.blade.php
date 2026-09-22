{{--
  WattVision: sidebar vertical persistente en desktop (>= md), drawer
  deslizante en mobile (< md). El state `sidebarOpen` viene del x-data
  padre en layouts/app.blade.php.

  Items en orden del usuario:
    Dashboard, Juegos, Ligas, Categorías, Torneos, Equipos, Atletas,
    Anotadores, Árbitros, Estadios, Usuarios (admin), Roles (admin),
    PWA Legacy (externo).
--}}

{{-- Backdrop solo mobile, click cierra drawer --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-wv-bg/80 z-40 md:hidden"
     x-cloak>
</div>

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-wv-bg border-r border-wv-border flex flex-col transition-transform duration-200 ease-in-out md:z-30"
    x-cloak>

    {{-- Logo / brand --}}
    <div class="h-16 flex items-center px-5 border-b border-wv-border flex-shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
            <span class="inline-flex items-center justify-center h-9 w-9 rounded-card bg-wv-accent-soft text-wv-accent">
                <span class="material-symbols-outlined text-[22px]">sports_baseball</span>
            </span>
            <div class="leading-tight">
                <div class="text-base font-bold text-wv-text">Gameday</div>
                <div class="text-[10px] uppercase tracking-widest text-wv-text-secondary">Score</div>
            </div>
        </a>

        {{-- Cerrar (solo mobile) --}}
        <button type="button"
                @click="sidebarOpen = false"
                class="md:hidden ms-auto inline-flex items-center justify-center w-9 h-9 rounded-card text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface focus:outline-none focus:ring-2 focus:ring-wv-accent">
            <span class="material-symbols-outlined text-[22px]">close</span>
        </button>
    </div>

    {{-- Menu principal (scrollable) --}}
    <nav class="flex-1 overflow-y-auto nav-scroll py-3">
        <div class="px-3 mb-2">
            <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-wv-text-secondary">
                {{ __('Principal') }}
            </p>
        </div>
        <div class="px-3 space-y-1">
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="dashboard">
                {{ __('Dashboard') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('games.index')" :active="request()->routeIs('games.*')" icon="sports_baseball">
                {{ __('Juegos') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('leagues.index')" :active="request()->routeIs('leagues.*')" icon="flag">
                {{ __('Ligas') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('categories.index')" :active="request()->routeIs('categories.*')" icon="category">
                {{ __('Categorías') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.*')" icon="emoji_events">
                {{ __('Torneos') }}
            </x-sidebar-link>
        </div>

        <div class="px-3 mt-5 mb-2">
            <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-wv-text-secondary">
                {{ __('Equipos & Personas') }}
            </p>
        </div>
        <div class="px-3 space-y-1">
            <x-sidebar-link :href="route('teams.index')" :active="request()->routeIs('teams.*')" icon="groups">
                {{ __('Equipos') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('athletes.index')" :active="request()->routeIs('athletes.*')" icon="person">
                {{ __('Atletas') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('scorekeepers.index')" :active="request()->routeIs('scorekeepers.*')" icon="edit_note">
                {{ __('Anotadores') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('referees.index')" :active="request()->routeIs('referees.*')" icon="sports">
                {{ __('Árbitros') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('stadiums.index')" :active="request()->routeIs('stadiums.*')" icon="stadium">
                {{ __('Estadios') }}
            </x-sidebar-link>
        </div>

        @auth
            @if (auth()->user()->isAdmin())
                <div class="px-3 mt-5 mb-2">
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-wv-text-secondary">
                        {{ __('Administración') }}
                    </p>
                </div>
                <div class="px-3 space-y-1">
                    <x-sidebar-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="manage_accounts">
                        {{ __('Usuarios') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" icon="shield_person">
                        {{ __('Roles') }}
                    </x-sidebar-link>
                    {{-- Log viewer de opcodesio. Restringido por 'admin'
                         middleware en config/log-viewer.php (auth+admin en
                         web y en api_middleware). Solo accesible desde aca. --}}
                    <x-sidebar-link href="/log-viewer" :active="request()->is('log-viewer*')" icon="terminal">
                        {{ __('Visor de logs') }}
                    </x-sidebar-link>
                </div>
            @endif
        @endauth

        <div class="px-3 mt-5 mb-2">
            <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-wv-text-secondary">
                {{ __('Otros') }}
            </p>
        </div>
        <div class="px-3 space-y-1">
            {{-- PWA Legacy: link externo al sitio v1.1.12 (DISI-82) --}}
            <x-sidebar-link href="/legacy/" icon="smartphone" external>
                {{ __('PWA Legacy') }}
            </x-sidebar-link>
        </div>
    </nav>

    {{-- Footer: usuario + logout --}}
    <div class="border-t border-wv-border p-3 flex-shrink-0" x-data="{ userMenuOpen: false }">
        <div class="relative">
            <button type="button"
                    @click="userMenuOpen = !userMenuOpen"
                    @click.outside="userMenuOpen = false"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-card hover:bg-wv-surface-hover focus:outline-none focus:ring-2 focus:ring-wv-accent transition">
                @if (Auth::user()->avatar_url)
                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}"
                         class="h-9 w-9 rounded-full object-cover bg-wv-surface flex-shrink-0">
                @else
                    <span class="h-9 w-9 rounded-full bg-wv-accent-soft text-wv-accent text-sm font-semibold inline-flex items-center justify-center flex-shrink-0">
                        {{ Auth::user()->avatar_initials }}
                    </span>
                @endif
                <div class="flex-1 text-left min-w-0">
                    <div class="text-sm font-semibold text-wv-text truncate">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-wv-text-secondary truncate">{{ Auth::user()->email }}</div>
                </div>
                <span class="material-symbols-outlined text-[18px] text-wv-text-secondary flex-shrink-0"
                      :class="userMenuOpen ? 'rotate-180' : ''"
                      style="transition: transform 200ms">
                    expand_less
                </span>
            </button>

            {{-- Menu flotante del usuario (sobre el sidebar) --}}
            <div x-show="userMenuOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-1"
                 class="absolute bottom-full left-0 right-0 mb-2 bg-wv-surface border border-wv-border rounded-card shadow-lg overflow-hidden"
                 x-cloak>
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-2 px-4 py-2.5 text-sm text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface-hover transition">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                    {{ __('Mi perfil') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-wv-alert hover:bg-wv-surface-hover transition text-left">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        {{ __('Cerrar sesion') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>