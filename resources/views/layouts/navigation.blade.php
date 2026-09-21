<nav x-data="{ open: false }" class="bg-wv-bg border-b border-wv-border">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-wv-accent" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('leagues.index')" :active="request()->routeIs('leagues.*')">
                        {{ __('Ligas') }}
                    </x-nav-link>
                    <x-nav-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.*')">
                        {{ __('Torneos') }}
                    </x-nav-link>
                    @auth
                        @if (auth()->user()->isAdmin())
                            <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                                {{ __('Usuarios') }}
                            </x-nav-link>
                            <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                                {{ __('Roles') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-wv-border text-sm leading-4 font-medium rounded-md text-wv-text bg-wv-surface hover:bg-wv-surface-hover hover:border-wv-border-strong focus:outline-none transition ease-in-out duration-150">
                            @if (Auth::user()->avatar_url)
                                <img src="{{ Auth::user()->avatar_url }}" alt="" class="h-7 w-7 rounded-full object-cover bg-wv-bg me-2">
                            @else
                                <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-wv-accent-soft text-wv-accent text-xs font-semibold me-2">
                                    {{ Auth::user()->avatar_initials }}
                                </span>
                            @endif
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Mi perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesion') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface focus:outline-none focus:bg-wv-surface focus:text-wv-text transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('leagues.index')" :active="request()->routeIs('leagues.*')">
                {{ __('Ligas') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.*')">
                {{ __('Torneos') }}
            </x-responsive-nav-link>
            @auth
                @if (auth()->user()->isAdmin())
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ __('Usuarios') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                        {{ __('Roles') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-wv-border">
            <div class="px-4">
                <div class="font-medium text-base text-wv-text">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-wv-text-secondary">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Mi perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesion') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>