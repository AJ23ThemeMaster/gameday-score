<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Control') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Mensaje de bienvenida --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-2">
                        {{ __('¡Bienvenido a Gameday Score!') }}
                    </h3>
                    <p class="text-sm text-gray-600">
                        {{ __('Has iniciado sesión como') }} <strong>{{ Auth::user()->name }}</strong>.
                        {{ __('Desde aquí podrás gestionar tus juegos, equipos, atletas y mucho más.') }}
                    </p>
                </div>
            </div>

            {{-- Tarjetas de acciones --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- DISI-7 (juegos) --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ __('Mis juegos') }}</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            {{ __('Consulta y gestiona los partidos que has creado.') }}
                        </p>
                        <span class="inline-block text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                            {{ __('Próximamente (DISI-7)') }}
                        </span>
                    </div>
                </div>

                {{-- DISI-4: Categorías (funcional) --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ __('Categorías') }}</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            {{ __('Administra las categorías de los torneos (Pre-Infantil, Profesional, etc.).') }}
                        </p>
                        <a href="{{ route('categories.index') }}"
                           class="inline-block text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700">
                            {{ __('Gestionar categorías') }}
                        </a>
                    </div>
                </div>

                {{-- DISI-4: Estadios (funcional) --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ __('Estadios') }}</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            {{ __('Registra los estadios donde se juegan los partidos.') }}
                        </p>
                        <a href="{{ route('stadiums.index') }}"
                           class="inline-block text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700">
                            {{ __('Gestionar estadios') }}
                        </a>
                    </div>
                </div>

                {{-- DISI-5: Equipos (funcional) --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ __('Equipos') }}</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            {{ __('Crea equipos con su logo.') }}
                        </p>
                        <a href="{{ route('teams.index') }}"
                           class="inline-block text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700">
                            {{ __('Gestionar equipos') }}
                        </a>
                    </div>
                </div>

                {{-- DISI-6 --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ __('Atletas') }}</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            {{ __('Gestiona los jugadores, anotadores y árbitros con su foto.') }}
                        </p>
                        <span class="inline-block text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                            {{ __('Próximamente (DISI-6)') }}
                        </span>
                    </div>
                </div>

                {{-- PWA Legacy --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ __('PWA Legacy (v1.1.12)') }}</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            {{ __('Accede a la versión clásica de Gameday Score que aún funciona sin login.') }}
                        </p>
                        <a href="/legacy/" target="_blank"
                           class="inline-block text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700">
                            {{ __('Abrir versión legacy') }}
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
