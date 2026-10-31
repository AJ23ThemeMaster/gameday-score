<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAthleteRequest;
use App\Http\Requests\UpdateAthleteRequest;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AthleteController extends Controller
{
    public function __construct()
    {
        // DISI-81 + DISI-delegado:
        //   index/show/edit/update/create/store: admin + gestor + delegado con scope.
        //     El delegado con scope (team_id + category_id no nulos) puede
        //     crear/editar atletas de su (equipo, categoria). El chequeo fino
        //     se hace dentro de store/update con User::isDelegadoOf().
        //   destroy: admin + gestor. El delegado NO elimina atletas.
        $this->middleware('admin_or_gestor_or_delegado')->except(['show', 'destroy']);
        // destroy queda restringido a admin + gestor (delegado no elimina).
        $this->middleware('admin_or_gestor')->only(['destroy']);
    }

    /**
     * DISI-81: gestor solo puede actuar sobre atletas de SU equipo asociado.
     * Admin puede actuar sobre cualquiera.
     */
    private function authorizeGestorOnAthlete(Athlete $athlete): void
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->isGestor() && ! $user->isGestorOwning($athlete)) {
            abort(403, 'Solo puedes administrar atletas del equipo al que estás asociado.');
        }
    }

    /**
     * DISI-delegado: chequeo combinado para gestor O delegado.
     * - Gestor: el atleta pertenece a SU equipo (sin importar la categoria).
     * - Delegado: el atleta pertenece a SU equipo Y alguna de sus categorias.
     * - Admin: pasa sin chequeo.
     */
    private function authorizeGestorOrDelegadoOnAthlete(Athlete $athlete): void
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (! $user) {
            abort(403, 'Necesitas iniciar sesión para acceder a esta sección.');
        }
        if ($user->isAdmin()) {
            return;
        }
        if ($user->isGestor() && ! $user->isGestorOwning($athlete)) {
            abort(403, 'Solo puedes administrar atletas del equipo al que estás asociado.');
        }
        if ($user->isDelegado() && ! $user->isDelegadoOf($athlete)) {
            abort(403, 'Solo puedes administrar atletas del equipo y la categoría a los que estás asociado.');
        }
    }

    public function index(Request $request): View
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $isGestor = $user && $user->isGestor();
        $isDelegado = $user && $user->isDelegado();

        // DISI-65: index filtrable (Nombre | Doc | N° | Pos. | Estado | Equipo | Categoria).
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'document_id' => trim((string) $request->query('document_id', '')),
            'number' => trim((string) $request->query('number', '')),
            'position' => (string) $request->query('position', ''),
            'status' => (string) $request->query('status', 'all'),
            'team_id' => $request->query('team_id'),
            'category_id' => $request->query('category_id'),
        ];
        $filters['status'] = in_array($filters['status'], ['active', 'inactive'], true) ? $filters['status'] : 'all';
        $filters['team_id'] = is_numeric($filters['team_id']) ? (int) $filters['team_id'] : null;
        $filters['category_id'] = is_numeric($filters['category_id']) ? (int) $filters['category_id'] : null;

        $q = Athlete::with(['team', 'categories']);

        // DISI-XXX + DISI-delegado: gestor ve SOLO atletas de su equipo;
        // delegado ve SOLO atletas de SU equipo Y SU categoria via pivot
        // athlete_category (scope automatico, no anulable por filtros).
        // Admin ve todos y puede filtrar por team_id/category_id libremente.
        if ($isGestor) {
            $q->where('team_id', $user->team_id);
            $filters['team_id'] = (int) $user->team_id;
        } elseif ($isDelegado) {
            // Scope doble para delegado: team_id + categoria via pivot.
            $q->where('team_id', $user->team_id);
            $q->whereHas('categories', function ($w) use ($user) {
                $w->where('categories.id', (int) $user->category_id);
            });
            $filters['team_id'] = (int) $user->team_id;
            $filters['category_id'] = (int) $user->category_id;
        }

        if ($filters['name'] !== '') {
            $like = '%'.$filters['name'].'%';
            $q->where(function ($w) use ($like) {
                $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like);
            });
        }
        if ($filters['document_id'] !== '') {
            $q->where('document_id', 'like', '%'.$filters['document_id'].'%');
        }
        if ($filters['number'] !== '' && is_numeric($filters['number'])) {
            $q->where('number', (int) $filters['number']);
        }
        if ($filters['position'] !== '') {
            $q->where('position', $filters['position']);
        }
        if ($filters['status'] === 'active') {
            $q->where('active', true);
        } elseif ($filters['status'] === 'inactive') {
            $q->where('active', false);
        }
        if ($filters['team_id'] !== null) {
            $q->where('team_id', $filters['team_id']);
        }
        // DISI-N: filtro por categoria via pivot.
        if ($filters['category_id'] !== null) {
            $q->whereHas('categories', function ($w) use ($filters) {
                $w->where('categories.id', $filters['category_id']);
            });
        }

        $athletes = $q
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        // DISI-XXX + DISI-delegado: el contador total refleja lo que el usuario
        // realmente puede ver. Para gestor es el total de su equipo;
        // para delegado es el total de su (equipo, categoria via pivot);
        // para admin es el total global.
        if ($isGestor || $isDelegado) {
            $totalAthletes = (clone $q)->toBase()->getCountForPagination();
        } else {
            $totalAthletes = Athlete::count();
        }
        $filteredCount = $athletes->total();

        // Posiciones: gestor y delegado solo ven las posiciones disponibles
        // en SU scope (atletas de su team). Admin ve todas las posiciones
        // del sistema (util cuando filtra por team_id).
        $positions = ($isGestor || $isDelegado)
            ? Athlete::whereNotNull('position')->where('team_id', $user->team_id)->distinct()->orderBy('position')->pluck('position')
            : Athlete::whereNotNull('position')->distinct()->orderBy('position')->pluck('position');
        // DISI-XXX + DISI-delegado: gestor y delegado solo ven su equipo
        // en el dropdown. Delegado ademas solo ve su categoria.
        $teams = ($isGestor || $isDelegado)
            ? Team::where('id', $user->team_id)->orderBy('name')->get()
            : Team::orderBy('name')->get();
        // Las categorias son globales (team_id=NULL) en este modelo, por
        // eso gestor y admin ven TODAS. Solo el delegado ve filtrada a
        // su categoria (porque su scope incluye categoria).
        $categories = $isDelegado
            ? Category::where('id', $user->category_id)->orderBy('name')->get()
            : Category::orderBy('name')->get();

        return view('athletes.index', compact(
            'athletes', 'totalAthletes', 'filteredCount',
            'filters', 'positions', 'teams', 'categories', 'isGestor', 'isDelegado'
        ));
    }

    public function create(): View
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        // DISI-81: gestor solo puede crear atletas para su equipo asociado.
        // DISI-piloto: delegado con scope (team + cat) ve solo su equipo +
        // su categoria en los dropdowns, pre-seleccionados para que el
        // form no le pida inventar valores.
        // DISI-N: ahora las categorias se asignan via multi-select; el
        // delegado arranca con su categoria pre-seleccionada en el array.
        if ($user->isDelegado()) {
            $teams = Team::where('id', (int) $user->team_id)->get();
            $categories = Category::orderBy('name')->get();
            $defaults = [
                'team_id' => (int) $user->team_id,
                'selected_category_ids' => [(int) $user->category_id],
            ];
        } elseif ($user->isGestor()) {
            $teams = Team::where('id', (int) $user->team_id)->get();
            $categories = Category::orderBy('name')->get();
            $defaults = [
                'team_id' => (int) $user->team_id,
                'selected_category_ids' => [],
            ];
        } else {
            $teams = Team::orderBy('name')->get();
            $categories = Category::orderBy('name')->get();
            $defaults = [
                'selected_category_ids' => [],
            ];
        }

        return view('athletes.create', array_merge(compact('teams', 'categories'),
            $defaults ? ['defaults' => $defaults] : []));
    }

    public function store(StoreAthleteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo para PHP 8.4 strict types
        $data['team_id'] = isset($data['team_id']) && $data['team_id'] !== null ? (int) $data['team_id'] : null;

        // DISI-81 + DISI-piloto:
        // - gestor solo puede crear atletas para SU equipo.
        // - delegado con scope: solo atletas de (su equipo, su categoria).
        //   La validacion fina de las categorias se hace via
        //   Athlete::syncCategories() (all categories must share team_id).
        //   El delegado ademas NO puede elegir otra categoria que la suya.
        //   Aunque la UI bloquee los selects con disabled, server-side se
        //   valida (defense in depth).
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->isGestor()) {
            $data['team_id'] = (int) $user->team_id;
        }
        if ($user && $user->isDelegado()) {
            $data['team_id'] = (int) $user->team_id;
            $sentTeam = (int) ($request->input('team_id') ?? 0);
            if ($sentTeam !== (int) $user->team_id) {
                abort(403, 'Como delegado solo puedes crear atletas de tu equipo.');
            }

            // Forzar a que la unica categoria permitida sea la del delegado.
            $sentCats = $request->input('categories', []);
            $sentCats = is_array($sentCats)
                ? collect($sentCats)->map(fn ($v) => (int) $v)->unique()->values()->all()
                : [];
            if ($sentCats !== [(int) $user->category_id]) {
                abort(403, 'Como delegado solo puedes crear atletas de tu categoría.');
            }
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
        }

        if ($request->hasFile('document_file')) {
            $data['document_file_path'] = $request->file('document_file')->store('athletes/documents', 'public');
        }

        $athlete = Athlete::create($data);

        // DISI-N: sincronizar las categorias via Athlete::syncCategories().
        // El helper valida max 2 + mismo equipo + existentes.
        $categoryIds = $request->input('categories', []);
        $categoryIds = is_array($categoryIds) ? array_map('intval', $categoryIds) : [];
        $athlete->syncCategories($categoryIds);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', "Atleta «{$athlete->full_name}» creado correctamente.");
    }

    public function show(Athlete $athlete): View
    {
        // DISI-delegado: gestor y delegado solo pueden ver atletas de su
        // scope. Admin pasa sin chequeo. La ruta show no tiene middleware
        // por lo que el chequeo fino lo hacemos aca.
        $this->authorizeGestorOrDelegadoOnAthlete($athlete);

        // DISI-62: cargamos team + categorias (pivot) + juegos del atleta
        // (via game_athlete pivot) y computamos stats agregadas + per-game
        // en una sola pasada.
        $athlete->load(['team', 'team.league', 'categories']);
        $categoryList = $athlete->categories()->orderBy('name')->get();

        // Juegos en los que el atleta participo (como bateador o como pitcher).
        // game_athlete es la pivot con team_id / lineup_order / position / is_pitcher.
        $games = \App\Models\Game::query()
            ->whereHas('athletes', function ($q) use ($athlete) {
                $q->where('athletes.id', $athlete->id);
            })
            ->orderByDesc('scheduled_at')
            ->limit(50)
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->get();

        // Stats generales (todos los juegos del atleta, sin agrupar)
        $careerBatting = $this->aggregateBatterStats($athlete->id);
        $careerPitching = $this->aggregatePitcherStats($athlete->id);

        // DISI-N: stats desglosadas por categoria (el atleta puede estar en
        // hasta 2 categorias del mismo equipo) y por (torneo x categoria).
        // $statsByCategory: una fila por categoria en la que el atleta tiene
        //   jugadas, con el flag 'in_roster' indicando si esa categoria
        //   esta en su pivot athlete_category.
        // $perTournamentByCategory: cada fila es (torneo, categoria) para que
        //   el desglose por torneo se pueda filtrar mas aun por categoria.
        [$statsByCategory, $perTournamentByCategory] = $this->aggregateStatsByDimensions($athlete->id);

        // Stats por juego (para la tabla)
        $perGame = [];
        foreach ($games as $g) {
            $b = \App\Models\Play::statsForBatter($g->id, $athlete->id);
            $p = \App\Models\Play::statsForPitcher($g->id, $athlete->id);
            $perGame[] = [
                'game' => $g,
                'batting' => $b,
                'pitching' => $p,
            ];
        }

        return view('athletes.show', compact(
            'athlete', 'games', 'careerBatting', 'careerPitching',
            'perGame', 'categoryList',
            'statsByCategory', 'perTournamentByCategory'
        ));
    }

    /**
     * DISI-62: stats de bateador AGREGADAS en todos los juegos del atleta
     * (no por juego). Es el equivalente de Play::statsForBatter pero sin
     * filtrar por game_id. Misma logica de conteo (hits, AB, K, BB, AVG).
     */
    private function aggregateBatterStats(int $athleteId): array
    {
        $plays = \App\Models\Play::where('batter_id', $athleteId)->get();
        return $this->batterStatsFromPlays($plays);
    }

    /**
     * DISI-62: stats de pitcher AGREGADAS en todos los juegos del atleta.
     * Equivalente de Play::statsForPitcher sin filtrar por game_id.
     */
    private function aggregatePitcherStats(int $athleteId): array
    {
        $plays = \App\Models\Play::where('pitcher_id', $athleteId)->get();
        return $this->pitcherStatsFromPlays($plays);
    }

    /**
     * DISI-N: el antiguo aggregateStatsByTournament() fue reemplazado por
     * aggregateStatsByDimensions(), que agrega el desglose por categoria
     * (games.category_id) ademas del desglose por torneo. Se elimino el
     * metodo anterior para no dejar dos caminos de calculo que puedan
     * divergir.
     */

    /**
     * DISI-N: agrega batting + pitching del atleta agrupado por DOS
     * dimensiones a la vez:
     *
     *  1. Por CATEGORIA del juego (games.category_id). Un atleta puede
     *     estar en hasta 2 categorias del mismo equipo, asi que sus
     *     estadisticas deben poder verse de forma independiente para cada
     *     una (ej. PEDRITO en Pre-infantil y en Infantil).
     *     La atribucion de una jugada a una categoria es la del JUEGO en
     *     el que ocurrio (no la de la pivot del atleta).
     *
     *  2. Por (TORNEO x CATEGORIA). Cada fila de la tabla "Estadisticas
     *     por torneo" lleva su categoria, de modo que el desglose por
     *     torneo se puede filtrar mas aun por categoria.
     *
     * Las jugadas de juegos sin categoria (games.category_id NULL) se
     * agrupan bajo la clave 'none' y se renderizan como "Sin categoria".
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     *         [0] = filas por categoria, [1] = filas por (torneo, categoria)
     */
    private function aggregateStatsByDimensions(int $athleteId): array
    {
        $plays = \App\Models\Play::with('game:id,category_id,tournament_id')
            ->where(function ($q) use ($athleteId) {
                $q->where('batter_id', $athleteId)
                  ->orWhere('pitcher_id', $athleteId);
            })
            ->get();

        $byCategory = [];
        $byTournamentCategory = [];

        foreach ($plays as $p) {
            $game = $p->game;
            $catId = $game?->category_id;
            $tid = $game?->tournament_id;
            $gid = $p->game_id;

            // --- Dimension 1: categoria ---
            $ckey = $catId === null ? 'none' : 'c:'.$catId;
            if (! isset($byCategory[$ckey])) {
                $byCategory[$ckey] = $this->emptyStatsRow(['category_id' => $catId]);
            }
            $row = &$byCategory[$ckey];
            if (! in_array($gid, $row['game_ids'], true)) {
                $row['game_ids'][] = $gid;
            }
            if ($p->batter_id === $athleteId) {
                $this->accumulateBatter($row['batting'], $p);
            }
            if ($p->pitcher_id === $athleteId) {
                $this->accumulatePitcher($row['pitching'], $p);
            }
            unset($row);

            // --- Dimension 2: torneo x categoria ---
            $tkey = ($tid === null ? 'none' : 't:'.$tid).'|'.($catId === null ? 'none' : 'c:'.$catId);
            if (! isset($byTournamentCategory[$tkey])) {
                $byTournamentCategory[$tkey] = $this->emptyStatsRow([
                    'tournament_id' => $tid,
                    'category_id' => $catId,
                ]);
            }
            $row = &$byTournamentCategory[$tkey];
            if (! in_array($gid, $row['game_ids'], true)) {
                $row['game_ids'][] = $gid;
            }
            if ($p->batter_id === $athleteId) {
                $this->accumulateBatter($row['batting'], $p);
            }
            if ($p->pitcher_id === $athleteId) {
                $this->accumulatePitcher($row['pitching'], $p);
            }
            unset($row);
        }

        // Marcar que categorias estan en la pivot del atleta (in_roster).
        $rosterCatIds = \App\Models\Athlete::find($athleteId)
            ?->categories()->pluck('categories.id')
            ->map(fn ($v) => (int) $v)
            ->all() ?? [];

        // DISI-N: garantizar que TODAS las categorias de la pivot del atleta
        // aparezcan en el desglose, aunque no tenga jugadas en ellas. Sin
        // esto, un atleta en Pre-infantil + Infantil que solo tiene plays
        // en una de ellas solo veria 1 fila (la otra desapareceria), que es
        // justo lo que el usuario quiere evitar: ver las 2 categorias
        // independientes siempre.
        // IMPORTANTE: esto va ANTES del batch query de modelos Category,
        // para que las categorias agregadas aqui tambien queden resueltas
        // (si se agrega despues, el modelo no existe en el map y la fila
        // se renderiza como "Sin categoria").
        foreach ($rosterCatIds as $rosterCatId) {
            $ckey = 'c:'.$rosterCatId;
            if (! isset($byCategory[$ckey])) {
                $byCategory[$ckey] = $this->emptyStatsRow(['category_id' => $rosterCatId]);
            }
        }

        // Resolver modelos Category + Tournament (batch query, sin N+1).
        $catIds = collect($byCategory)->pluck('category_id')->filter()->unique()->values();
        $tIds = collect($byTournamentCategory)->pluck('tournament_id')->filter()->unique()->values();
        $categories = \App\Models\Category::whereIn('id', $catIds)->get()->keyBy('id');
        $tournaments = \App\Models\Tournament::whereIn('id', $tIds)->get()->keyBy('id');

        // JJ por categoria: juegos en los que el atleta estuvo en el roster
        // (game_athlete), independientemente de si luego tuvo jugadas. Esto
        // permite que una categoria de la pivot sin jugadas muestre JJ=0 en
        // vez de desaparecer del desglose.
        $gamesByCategory = \Illuminate\Support\Facades\DB::table('game_athlete')
            ->join('games', 'games.id', '=', 'game_athlete.game_id')
            ->where('game_athlete.athlete_id', $athleteId)
            ->groupBy('games.category_id')
            ->select('games.category_id', \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT games.id) as c'))
            ->pluck('c', 'games.category_id')
            ->all();

        foreach ($byCategory as &$row) {
            $this->finalizeStatsRow($row);
            $row['category'] = $row['category_id'] ? ($categories[$row['category_id']] ?? null) : null;
            $row['in_roster'] = $row['category_id'] !== null && in_array((int) $row['category_id'], $rosterCatIds, true);
            // JJ = juegos del atleta en esa categoria (game_athlete). Si no
            // hay info (categoria suelta sin juegos), se conserva el
            // conteo de juegos con jugadas.
            $rosterGames = $row['category_id'] !== null
                ? (int) ($gamesByCategory[$row['category_id']] ?? 0)
                : 0;
            $row['games_count'] = max($row['games_count'], $rosterGames);
        }
        unset($row);

        foreach ($byTournamentCategory as &$row) {
            $this->finalizeStatsRow($row);
            $row['category'] = $row['category_id'] ? ($categories[$row['category_id']] ?? null) : null;
            $row['tournament'] = $row['tournament_id'] ? ($tournaments[$row['tournament_id']] ?? null) : null;
        }
        unset($row);

        // Ordenar por categoria (nombre alfabetico, "Sin categoria" al final).
        $categoryRows = collect($byCategory)
            ->sortBy(function ($r) {
                return $r['category'] ? $r['category']->name : 'ZZZ-Sin categoria';
            })
            ->values();

        // Ordenar por torneo y luego por categoria.
        $tournamentRows = collect($byTournamentCategory)
            ->sortBy([
                fn ($r) => $r['tournament'] ? $r['tournament']->name : 'ZZZ-Sin torneo',
                fn ($r) => $r['category'] ? $r['category']->name : 'ZZZ-Sin categoria',
            ])
            ->values();

        return [$categoryRows, $tournamentRows];
    }

    /**
     * Row vacia con los contadores inicializados en 0.
     */
    private function emptyStatsRow(array $extra = []): array
    {
        return array_merge([
            'game_ids' => [],
            'batting' => ['at_bats' => 0, 'hits' => 0, 'strikeouts' => 0, 'walks' => 0],
            'pitching' => ['pitches' => 0, 'strikes' => 0, 'balls' => 0, 'strikeouts' => 0, 'hits' => 0, 'walks' => 0],
        ], $extra);
    }

    /**
     * Calcula AVG + games_count y descarta game_ids.
     */
    private function finalizeStatsRow(array &$row): void
    {
        $b = &$row['batting'];
        $b['avg'] = $b['at_bats'] > 0 ? round($b['hits'] / $b['at_bats'], 3) : 0.0;
        unset($b);
        $row['games_count'] = count($row['game_ids']);
        unset($row['game_ids']);
    }

    /**
     * Helper: devuelve las stats de bateo a partir de una Collection de plays
     * donde el atleta figura como batter (caller filtra previamente).
     */
    private function batterStatsFromPlays(iterable $plays): array
    {
        $stats = ['at_bats' => 0, 'hits' => 0, 'strikeouts' => 0, 'walks' => 0];
        foreach ($plays as $p) {
            $this->accumulateBatter($stats, $p);
        }
        $stats['avg'] = $stats['at_bats'] > 0 ? round($stats['hits'] / $stats['at_bats'], 3) : 0.0;
        return $stats;
    }

    /**
     * Helper: devuelve las stats de pitcheo a partir de una Collection de plays
     * donde el atleta figura como pitcher (caller filtra previamente).
     */
    private function pitcherStatsFromPlays(iterable $plays): array
    {
        $stats = ['pitches' => 0, 'strikes' => 0, 'balls' => 0, 'strikeouts' => 0, 'hits' => 0, 'walks' => 0];
        foreach ($plays as $p) {
            $this->accumulatePitcher($stats, $p);
        }
        return $stats;
    }

    /**
     * Suma una jugada a un acumulador de bateo (mutates $stats in place).
     */
    private function accumulateBatter(array &$stats, \App\Models\Play $p): void
    {
        $type = $p->type;
        if ($type === \App\Models\Play::TYPE_HIT) {
            $stats['at_bats']++;
            $stats['hits']++;
        } elseif ($type === \App\Models\Play::TYPE_OUT) {
            $stats['at_bats']++;
            if ($p->subtype === \App\Models\Play::SUBTYPE_OUT_STRIKEOUT) {
                $stats['strikeouts']++;
            }
        } elseif ($type === \App\Models\Play::TYPE_WALK) {
            $stats['walks']++;
        } elseif ($type === \App\Models\Play::TYPE_HBP) {
            // No cuenta como AB ni hit
        } elseif ($type === \App\Models\Play::TYPE_BUNT) {
            if ($p->subtype !== \App\Models\Play::SUBTYPE_BUNT_SACRIFICE) {
                $stats['at_bats']++;
                if ($p->subtype === \App\Models\Play::SUBTYPE_BUNT_SINGLE) {
                    $stats['hits']++;
                }
            }
        }
    }

    /**
     * Suma una jugada a un acumulador de pitcheo (mutates $stats in place).
     */
    private function accumulatePitcher(array &$stats, \App\Models\Play $p): void
    {
        $type = $p->type;
        $subtype = $p->subtype;
        if ($type === \App\Models\Play::TYPE_PITCH) {
            if ($subtype === 'at_bat_start') {
                return;
            }
            $stats['pitches']++;
            if ($subtype === 'ball') {
                $stats['balls']++;
            } elseif (in_array($subtype, ['looking', 'swinging', 'foul_tip'], true)) {
                $stats['strikes']++;
            }
        } elseif ($type === \App\Models\Play::TYPE_OUT && $subtype === \App\Models\Play::SUBTYPE_OUT_STRIKEOUT) {
            $stats['strikeouts']++;
        } elseif ($type === \App\Models\Play::TYPE_HIT) {
            $stats['hits']++;
        } elseif ($type === \App\Models\Play::TYPE_WALK) {
            $stats['walks']++;
        }
    }

    public function edit(Athlete $athlete): View
    {
        $this->authorizeGestorOrDelegadoOnAthlete($athlete);
        $user = \Illuminate\Support\Facades\Auth::user();
        // DISI-81 + DISI-delegado: gestor y delegado solo ven su equipo
        // en el dropdown. Delegado ademas solo ve su categoria.
        $teams = ($user && ($user->isGestor() || $user->isDelegado()))
            ? Team::where('id', $user->team_id)->get()
            : Team::orderBy('name')->get();

        // DISI-N: el multi-select de categorias. Admin/gestor ven todas las
        // categorias; delegado solo ve SU categoria.
        if ($user && $user->isDelegado()) {
            $categories = Category::where('id', (int) $user->category_id)->orderBy('name')->get();
        } else {
            $categories = Category::orderBy('name')->get();
        }

        // Categorias actualmente asignadas al atleta (puede ser 1 o 2).
        $selectedCategoryIds = $athlete->categories()->pluck('categories.id')->map(fn ($v) => (int) $v)->all();

        return view('athletes.edit', compact('athlete', 'teams', 'categories', 'selectedCategoryIds'));
    }

    public function update(UpdateAthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        $this->authorizeGestorOrDelegadoOnAthlete($athlete);
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $athlete->active);
        $data['team_id'] = isset($data['team_id']) && $data['team_id'] !== null ? (int) $data['team_id'] : null;

        // DISI-81: gestor no puede cambiar el equipo del atleta a uno que
        // no sea el suyo.
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->isGestor()) {
            $data['team_id'] = $user->team_id;
        }

        // DISI-N + DISI-piloto delegado: si el delegado intenta cambiar team
        // fuera de scope, abort.
        if ($user->isDelegado() && (int) $data['team_id'] !== (int) $user->team_id) {
            abort(403, 'Como delegado solo puedes modificar atletas de tu equipo.');
        }

        if ($request->hasFile('photo')) {
            $this->deletePhoto($athlete);
            $data['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($athlete);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('document_file')) {
            $this->deleteDocument($athlete);
            $data['document_file_path'] = $request->file('document_file')->store('athletes/documents', 'public');
        } elseif ($request->boolean('remove_document')) {
            $this->deleteDocument($athlete);
            $data['document_file_path'] = null;
        }

        $athlete->update($data);

        // DISI-N: sincronizar categorias via Athlete::syncCategories().
        // El helper valida max 2 + mismo equipo + existentes.
        $categoryIds = $request->input('categories', []);
        $categoryIds = is_array($categoryIds) ? array_map('intval', $categoryIds) : [];

        // DISI-piloto: delegado solo puede mantener SU categoria, no agregar otras.
        if ($user->isDelegado()) {
            $categoryIds = [(int) $user->category_id];
        }

        $athlete->syncCategories($categoryIds);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', "Atleta «{$athlete->full_name}» actualizado correctamente.");
    }

    public function destroy(Athlete $athlete): RedirectResponse
    {
        // DISI-XXX + DISI-delegado: gestor solo puede eliminar atletas
        // de SU equipo. Delegado NO puede eliminar (su rol es solo ver
        // y editar). El middleware 'admin_or_gestor' ya bloquea al
        // delegado en la ruta; este check explicito queda como
        // defense-in-depth por si el middleware se relaja en el futuro.
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->isDelegado()) {
            abort(403, 'No tienes permiso para eliminar atletas.');
        }
        $this->authorizeGestorOnAthlete($athlete);

        $name = $athlete->full_name;
        $this->deletePhoto($athlete);
        $this->deleteDocument($athlete);
        $athlete->delete();

        return redirect()
            ->route('athletes.index')
            ->with('status', "Atleta «{$name}» eliminado correctamente.");
    }

    private function deletePhoto(Athlete $athlete): void
    {
        if ($athlete->photo_path && Storage::disk('public')->exists($athlete->photo_path)) {
            Storage::disk('public')->delete($athlete->photo_path);
        }
    }

    private function deleteDocument(Athlete $athlete): void
    {
        if ($athlete->document_file_path && Storage::disk('public')->exists($athlete->document_file_path)) {
            Storage::disk('public')->delete($athlete->document_file_path);
        }
    }
}
