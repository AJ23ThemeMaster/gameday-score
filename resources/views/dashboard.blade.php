{{--
  Dashboard redise~ado al sistema WattVision (feature/style/wattvision).
  Style guide: DESIGN.md.
  Aplicado SOLO al dashboard. Scoreboard, box-score y live quedan intactos.
--}}
@php
    // KPIs en vivo. try/catch defensivo: si una tabla no existe aun (ej. fresh
    // migrate sin seeds), no rompemos el render del dashboard.
    $kpis = [
        'games'   => 0,
        'teams'   => 0,
        'athletes' => 0,
    ];
    try { $kpis['games']    = \App\Models\Game::count(); } catch (\Throwable $e) {}
    try { $kpis['teams']    = \App\Models\Team::count(); } catch (\Throwable $e) {}
    try { $kpis['athletes'] = \App\Models\Athlete::count(); } catch (\Throwable $e) {}

    // Juegos del dia (jornada): scheduled_at entre hoy 00:00 y hoy 23:59.
    // Estados que muestran marcador: in_progress, paused, completed.
    // Estados sin marcador (solo programacion): scheduled.
    // Filtramos por usuario autenticado: admins ven todos; gestores y otros
    // solo los juegos donde estan involucrados como staff (anotadores o
    // arbitros), igual que la seccion "Mis juegos" del sidebar.
    $todayGames = collect();
    try {
        $todayQuery = \App\Models\Game::query()
            ->with(['homeTeam', 'awayTeam', 'category', 'stadium'])
            ->whereIn('status', ['scheduled', 'in_progress', 'paused', 'completed'])
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('scheduled_at');

        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && ! $user->isAdmin()) {
            // Gestores / anotadores / arbitros: solo juegos donde estan asignados.
            $todayQuery->where(function ($q) use ($user) {
                $q->whereHas('scorekeepers', fn ($sq) => $sq->where('users.id', $user->id))
                  ->orWhereHas('referees', fn ($rq) => $rq->where('users.id', $user->id));
            });
        }

        $todayGames = $todayQuery->get();
    } catch (\Throwable $e) {
        $todayGames = collect();
    }
@endphp

<x-app-layout>
    {{-- El slot del header ya no muestra "Panel de Control / Vista general".
         En su lugar, en esta misma zona del layout se inyecta el carousel de
         juegos del dia (via componente game-day-card) con flechas de scroll
         si hay mas de 3 juegos (en lg caben 3 cards, el 4to ya hace overflow). --}}

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- ===========================================================
                 CARRUSEL: Juegos del dia (jornada)
                 - 1 fila de cards de 1/3 del ancho (col-3 en desktop lg).
                 - Si hay > 3 juegos aparecen flechas izquierda/derecha que
                   hacen scrollBy de aprox 1 card por click.
                 - Empty state cuando no hay juegos del dia.
                 =========================================================== --}}
            <div x-data="{
                        scroller: null,
                        scrollPrev() { if (this.scroller) this.scroller.scrollBy({left: -this.scroller.clientWidth * 0.8, behavior: 'smooth'}); },
                        scrollNext() { if (this.scroller) this.scroller.scrollBy({left: this.scroller.clientWidth * 0.8,  behavior: 'smooth'}); }
                    }"
                 x-init="scroller = $refs.carousel"
                 class="relative mb-6">

                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="font-semibold text-h-wv text-wv-text leading-tight">
                            {{ __('Juegos del dia') }}
                        </h2>
                        <p class="text-xs text-wv-text-secondary mt-0.5">
                            {{ __('Jornada de hoy') }} ·
                            <span class="font-mono">{{ now()->translatedFormat('d \\d\\e F, Y') }}</span>
                            · {{ $todayGames->count() }}
                            {{ $todayGames->count() === 1 ? __('juego') : __('juegos') }}
                        </p>
                    </div>

                    {{-- Flechas: aparecen a partir de 4 juegos (en lg caben 3 cards,
                         asi que el 4to ya hace overflow y requiere scroll). --}}
                    @if ($todayGames->count() > 3)
                        <div class="flex gap-1">
                            <button type="button"
                                    @click="scrollPrev()"
                                    aria-label="{{ __('Anterior') }}"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-card bg-wv-surface border border-wv-border text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface-hover hover:border-wv-accent transition">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </button>
                            <button type="button"
                                    @click="scrollNext()"
                                    aria-label="{{ __('Siguiente') }}"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-card bg-wv-surface border border-wv-border text-wv-text-secondary hover:text-wv-text hover:bg-wv-surface-hover hover:border-wv-accent transition">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </button>
                        </div>
                    @endif
                </div>

                @if ($todayGames->isEmpty())
                    {{-- Empty state: no hay juegos en la jornada --}}
                    <div class="bg-wv-surface border border-wv-border rounded-card p-10 text-center">
                        <span class="material-symbols-outlined text-wv-text-secondary text-[48px]">event_busy</span>
                        <h3 class="mt-2 text-base font-semibold text-wv-text">{{ __('Sin juegos para hoy') }}</h3>
                        <p class="mt-1 text-sm text-wv-text-secondary">{{ __('No tienes juegos programados, en vivo ni finalizados en la jornada de hoy.') }}</p>
                        <a href="{{ route('games.index') }}"
                           class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-wv-accent hover:text-wv-accent-hover">
                            {{ __('Ir al listado de juegos') }}
                            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                @else
                    {{-- Scroller horizontal con snap --}}
                    <div x-ref="carousel"
                         class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2 -mx-2 px-2 nav-scroll">

                        @foreach ($todayGames as $g)
                            <x-game-day-card :game="$g" />
                        @endforeach

                    </div>
                @endif

            </div>

            {{-- ===========================================================
                 FILA 2: KPI cards (3 columnas) - Diseno WattVision §5
                 =========================================================== --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

                {{-- KPI: total de juegos --}}
                <div class="bg-wv-surface border border-wv-border rounded-card p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-wv-text-secondary uppercase tracking-wider">{{ __('Juegos') }}</span>
                        <span class="material-symbols-outlined text-wv-accent text-[20px]">sports_baseball</span>
                    </div>
                    <div class="font-mono text-kpi text-wv-text">
                        {{ $kpis['games'] }}
                    </div>
                    <div class="text-xs text-wv-text-secondary mt-2 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-wv-success animate-pulse"></span>
                        {{ __('En vivo ahora') }}
                    </div>
                </div>

                {{-- KPI: total de equipos --}}
                <div class="bg-wv-surface border border-wv-border rounded-card p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-wv-text-secondary uppercase tracking-wider">{{ __('Equipos') }}</span>
                        <span class="material-symbols-outlined text-wv-accent text-[20px]">groups</span>
                    </div>
                    <div class="font-mono text-kpi text-wv-text">
                        {{ $kpis['teams'] }}
                    </div>
                    <div class="text-xs text-wv-text-secondary mt-2">
                        {{ __('Registrados en el sistema') }}
                    </div>
                </div>

                {{-- KPI: total de atletas --}}
                <div class="bg-wv-surface border border-wv-border rounded-card p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-wv-text-secondary uppercase tracking-wider">{{ __('Atletas') }}</span>
                        <span class="material-symbols-outlined text-wv-accent text-[20px]">person</span>
                    </div>
                    <div class="font-mono text-kpi text-wv-text">
                        {{ $kpis['athletes'] }}
                    </div>
                    <div class="text-xs text-wv-text-secondary mt-2">
                        {{ __('En todos los rosters') }}
                    </div>
                </div>

            </div>

            {{-- ===========================================================
                 FILA 3: bienvenida + acceso rapido a legacy
                 8 columnas izquierda (bienvenida), 4 columnas derecha (legacy)
                 =========================================================== --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">

                {{-- Bienvenida (8 cols) --}}
                <div class="lg:col-span-8 bg-wv-surface border border-wv-border rounded-card p-5">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-wv-accent text-[28px] flex-shrink-0">waving_hand</span>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-h-wv font-semibold text-wv-text mb-2">
                                {{ __('¡Bienvenido a Gameday Score!') }}
                            </h3>
                            <p class="text-sm text-wv-text-secondary leading-relaxed">
                                {{ __('Has iniciado sesion como') }}
                                <strong class="text-wv-text font-semibold">{{ Auth::user()->name }}</strong>.
                                {{ __('Desde aqui podras gestionar tus juegos, equipos, atletas y mucho mas.') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- PWA Legacy (4 cols) - card destacada con border cyan --}}
                <div class="lg:col-span-4 bg-wv-surface border-2 border-wv-accent/40 rounded-card p-5 relative overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-wv-accent/10 rounded-full blur-2xl"></div>

                    <div class="relative">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-wv-text-secondary uppercase tracking-wider">{{ __('PWA Legacy') }}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 bg-wv-accent/20 text-wv-accent rounded-full">v1.1.12</span>
                        </div>
                        <h4 class="text-base font-semibold text-wv-text mb-1">{{ __('Version sin login') }}</h4>
                        <p class="text-xs text-wv-text-secondary mb-4 leading-relaxed">
                            {{ __('Accede a la version clasica que aun funciona sin autenticacion.') }}
                        </p>
                        <a href="/legacy/" target="_blank"
                           class="inline-flex items-center gap-2 bg-wv-accent hover:bg-wv-accent-hover text-wv-bg font-semibold text-sm px-4 py-2 rounded-card transition">
                            <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                            {{ __('Abrir legacy') }}
                        </a>
                    </div>
                </div>

            </div>

            {{-- ===========================================================
                 FILA 4: Modulos principales (grid 3 columnas)
                 =========================================================== --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <a href="{{ route('games.index') }}"
                   class="group bg-wv-surface border border-wv-border hover:border-wv-accent/50 hover:bg-wv-surface-hover rounded-card p-5 transition block">
                    <div class="flex items-center justify-between mb-3">
                        <span class="material-symbols-outlined text-wv-accent text-[28px]">sports_baseball</span>
                        <span class="material-symbols-outlined text-wv-text-secondary text-[18px] group-hover:text-wv-accent transition">arrow_forward</span>
                    </div>
                    <h4 class="text-base font-semibold text-wv-text mb-1">{{ __('Mis juegos') }}</h4>
                    <p class="text-xs text-wv-text-secondary mb-3 leading-relaxed">
                        {{ __('Crea y gestiona tus partidos con sus equipos, anotadores y arbitros.') }}
                    </p>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-wv-accent group-hover:gap-2 transition-all">
                        {{ __('Gestionar') }}
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </span>
                </a>

                <a href="{{ route('categories.index') }}"
                   class="group bg-wv-surface border border-wv-border hover:border-wv-accent/50 hover:bg-wv-surface-hover rounded-card p-5 transition block">
                    <div class="flex items-center justify-between mb-3">
                        <span class="material-symbols-outlined text-wv-accent text-[28px]">category</span>
                        <span class="material-symbols-outlined text-wv-text-secondary text-[18px] group-hover:text-wv-accent transition">arrow_forward</span>
                    </div>
                    <h4 class="text-base font-semibold text-wv-text mb-1">{{ __('Categorias') }}</h4>
                    <p class="text-xs text-wv-text-secondary mb-3 leading-relaxed">
                        {{ __('Administra las categorias de los torneos (Pre-Infantil, Profesional, etc.).') }}
                    </p>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-wv-accent group-hover:gap-2 transition-all">
                        {{ __('Gestionar') }}
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </span>
                </a>

                <a href="{{ route('stadiums.index') }}"
                   class="group bg-wv-surface border border-wv-border hover:border-wv-accent/50 hover:bg-wv-surface-hover rounded-card p-5 transition block">
                    <div class="flex items-center justify-between mb-3">
                        <span class="material-symbols-outlined text-wv-accent text-[28px]">stadium</span>
                        <span class="material-symbols-outlined text-wv-text-secondary text-[18px] group-hover:text-wv-accent transition">arrow_forward</span>
                    </div>
                    <h4 class="text-base font-semibold text-wv-text mb-1">{{ __('Estadios') }}</h4>
                    <p class="text-xs text-wv-text-secondary mb-3 leading-relaxed">
                        {{ __('Registra los estadios donde se juegan los partidos.') }}
                    </p>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-wv-accent group-hover:gap-2 transition-all">
                        {{ __('Gestionar') }}
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </span>
                </a>

                <a href="{{ route('teams.index') }}"
                   class="group bg-wv-surface border border-wv-border hover:border-wv-accent/50 hover:bg-wv-surface-hover rounded-card p-5 transition block">
                    <div class="flex items-center justify-between mb-3">
                        <span class="material-symbols-outlined text-wv-accent text-[28px]">shield</span>
                        <span class="material-symbols-outlined text-wv-text-secondary text-[18px] group-hover:text-wv-accent transition">arrow_forward</span>
                    </div>
                    <h4 class="text-base font-semibold text-wv-text mb-1">{{ __('Equipos') }}</h4>
                    <p class="text-xs text-wv-text-secondary mb-3 leading-relaxed">
                        {{ __('Crea equipos con su logo.') }}
                    </p>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-wv-accent group-hover:gap-2 transition-all">
                        {{ __('Gestionar') }}
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </span>
                </a>

                <a href="{{ route('athletes.index') }}"
                   class="group bg-wv-surface border border-wv-border hover:border-wv-accent/50 hover:bg-wv-surface-hover rounded-card p-5 transition block">
                    <div class="flex items-center justify-between mb-3">
                        <span class="material-symbols-outlined text-wv-accent text-[28px]">sports_handball</span>
                        <span class="material-symbols-outlined text-wv-text-secondary text-[18px] group-hover:text-wv-accent transition">arrow_forward</span>
                    </div>
                    <h4 class="text-base font-semibold text-wv-text mb-1">{{ __('Atletas') }}</h4>
                    <p class="text-xs text-wv-text-secondary mb-3 leading-relaxed">
                        {{ __('Gestiona los jugadores con su foto, numero y posicion.') }}
                    </p>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-wv-accent group-hover:gap-2 transition-all">
                        {{ __('Gestionar') }}
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </span>
                </a>

                <a href="{{ route('scorekeepers.index') }}"
                   class="group bg-wv-surface border border-wv-border hover:border-wv-accent/50 hover:bg-wv-surface-hover rounded-card p-5 transition block">
                    <div class="flex items-center justify-between mb-3">
                        <span class="material-symbols-outlined text-wv-accent text-[28px]">edit_note</span>
                        <span class="material-symbols-outlined text-wv-text-secondary text-[18px] group-hover:text-wv-accent transition">arrow_forward</span>
                    </div>
                    <h4 class="text-base font-semibold text-wv-text mb-1">{{ __('Anotadores') }}</h4>
                    <p class="text-xs text-wv-text-secondary mb-3 leading-relaxed">
                        {{ __('Personas encargadas de registrar el juego en planilla.') }}
                    </p>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-wv-accent group-hover:gap-2 transition-all">
                        {{ __('Gestionar') }}
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </span>
                </a>

                <a href="{{ route('referees.index') }}"
                   class="group bg-wv-surface border border-wv-border hover:border-wv-accent/50 hover:bg-wv-surface-hover rounded-card p-5 transition block md:col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between mb-3">
                        <span class="material-symbols-outlined text-wv-accent text-[28px]">sports</span>
                        <span class="material-symbols-outlined text-wv-text-secondary text-[18px] group-hover:text-wv-accent transition">arrow_forward</span>
                    </div>
                    <h4 class="text-base font-semibold text-wv-text mb-1">{{ __('Arbitros') }}</h4>
                    <p class="text-xs text-wv-text-secondary mb-3 leading-relaxed">
                        {{ __('Arbitros principales y de base con su certificacion.') }}
                    </p>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-wv-accent group-hover:gap-2 transition-all">
                        {{ __('Gestionar') }}
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </span>
                </a>

            </div>

        </div>
    </div>
</x-app-layout>