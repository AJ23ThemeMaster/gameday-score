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
        // DISI-81: gestor puede crear/editar atletas pero solo de su equipo.
        // Index y destroy siguen siendo admin-only para no exponer la lista completa.
        $this->middleware('admin_or_gestor')->except(['show', 'destroy']);
        $this->middleware('admin')->only(['index', 'destroy']);
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

    public function index(Request $request): View
    {
        // DISI-65: index filtrable (Nombre | Doc | N° | Pos. | Estado | Equipo | Categoria).
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'document_id' => trim((string) $request->query('document_id', '')),
            'number' => trim((string) $request->query('number', '')),
            'position' => trim((string) $request->query('position', '')),
            'status' => (string) $request->query('status', 'all'),
            'team_id' => $request->query('team_id'),
            'category_id' => $request->query('category_id'),
        ];
        $filters['status'] = in_array($filters['status'], ['active', 'inactive'], true) ? $filters['status'] : 'all';
        $filters['team_id'] = is_numeric($filters['team_id']) ? (int) $filters['team_id'] : null;
        $filters['category_id'] = is_numeric($filters['category_id']) ? (int) $filters['category_id'] : null;

        $q = Athlete::with(['team', 'category']);

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
        if ($filters['category_id'] !== null) {
            $q->where('category_id', $filters['category_id']);
        }

        $athletes = $q
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        $totalAthletes = Athlete::count();
        $filteredCount = $athletes->total();

        $positions = Athlete::whereNotNull('position')->distinct()->orderBy('position')->pluck('position');
        $teams = Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('athletes.index', compact(
            'athletes', 'totalAthletes', 'filteredCount',
            'filters', 'positions', 'teams', 'categories'
        ));
    }

    public function create(): View
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        // DISI-81: gestor solo puede crear atletas para su equipo asociado.
        $teams = $user->isGestor()
            ? Team::where('id', $user->team_id)->get()
            : Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('athletes.create', compact('teams', 'categories'));
    }

    public function store(StoreAthleteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo para PHP 8.4 strict types
        $data['team_id'] = isset($data['team_id']) && $data['team_id'] !== null ? (int) $data['team_id'] : null;
        $data['category_id'] = isset($data['category_id']) && $data['category_id'] !== null ? (int) $data['category_id'] : null;

        // DISI-81: gestor solo puede crear atletas para su equipo asociado.
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->isGestor()) {
            $data['team_id'] = $user->team_id;
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
        }

        if ($request->hasFile('document_file')) {
            $data['document_file_path'] = $request->file('document_file')->store('athletes/documents', 'public');
        }

        $athlete = Athlete::create($data);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', "Atleta «{$athlete->full_name}» creado correctamente.");
    }

    public function show(Athlete $athlete): View
    {
        // DISI-62: cargamos team + category + juegos del atleta (via game_athlete
        // pivot) y computamos stats agregadas + per-game en una sola pasada.
        $athlete->load(['team', 'category', 'team.league']);

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

        // DISI-79: stats agrupadas por torneo (batting + pitching + games count)
        $perTournament = $this->aggregateStatsByTournament($athlete->id);

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
            'athlete', 'games', 'careerBatting', 'careerPitching', 'perTournament', 'perGame'
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
     * DISI-79: agrega batting + pitching del atleta, agrupado por
     * tournament_id (viene de play->game->tournament_id). Las jugadas
     * sin torneo (tournament_id NULL) se agrupan bajo la clave 'none'
     * y se renderizan como "Sin torneo".
     */
    private function aggregateStatsByTournament(int $athleteId): \Illuminate\Support\Collection
    {
        $plays = \App\Models\Play::with('game:id,tournament_id')
            ->where(function ($q) use ($athleteId) {
                $q->where('batter_id', $athleteId)
                  ->orWhere('pitcher_id', $athleteId);
            })
            ->get();

        $byTournament = [];
        foreach ($plays as $p) {
            $tid = $p->game?->tournament_id;
            $key = $tid === null ? 'none' : 't:' . $tid;
            if (! isset($byTournament[$key])) {
                $byTournament[$key] = [
                    'tournament_id' => $tid,
                    'game_ids' => [],
                    'batting' => ['at_bats' => 0, 'hits' => 0, 'strikeouts' => 0, 'walks' => 0],
                    'pitching' => ['pitches' => 0, 'strikes' => 0, 'balls' => 0, 'strikeouts' => 0, 'hits' => 0, 'walks' => 0],
                ];
            }
            $gid = $p->game_id;
            if (! in_array($gid, $byTournament[$key]['game_ids'], true)) {
                $byTournament[$key]['game_ids'][] = $gid;
            }
            $row = &$byTournament[$key];
            if ($p->batter_id === $athleteId) {
                $this->accumulateBatter($row['batting'], $p);
            }
            if ($p->pitcher_id === $athleteId) {
                $this->accumulatePitcher($row['pitching'], $p);
            }
            unset($row);
        }

        // Calcular AVG + games_count, descartar game_ids
        foreach ($byTournament as &$row) {
            $b = &$row['batting'];
            $b['avg'] = $b['at_bats'] > 0 ? round($b['hits'] / $b['at_bats'], 3) : 0.0;
            unset($b);
            $row['games_count'] = count($row['game_ids']);
            unset($row['game_ids']);
        }
        unset($row);

        // Resolver modelos Tournament para los IDs conocidos (batch query).
        $tIds = collect($byTournament)->pluck('tournament_id')->filter()->unique()->values();
        $tournaments = \App\Models\Tournament::whereIn('id', $tIds)->get()->keyBy('id');
        foreach ($byTournament as &$row) {
            $row['tournament'] = $row['tournament_id'] ? ($tournaments[$row['tournament_id']] ?? null) : null;
        }
        unset($row);

        // Ordenar: primero torneos con nombre, luego "Sin torneo" al final.
        return collect($byTournament)
            ->sortBy(function ($r) {
                return $r['tournament'] ? $r['tournament']->name : 'ZZZ-Sin torneo';
            })
            ->values();
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
        $this->authorizeGestorOnAthlete($athlete);
        $user = \Illuminate\Support\Facades\Auth::user();
        // DISI-81: gestor solo puede ver atletas de su equipo.
        $teams = ($user && $user->isGestor())
            ? Team::where('id', $user->team_id)->get()
            : Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('athletes.edit', compact('athlete', 'teams', 'categories'));
    }

    public function update(UpdateAthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        $this->authorizeGestorOnAthlete($athlete);
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $athlete->active);
        $data['team_id'] = isset($data['team_id']) && $data['team_id'] !== null ? (int) $data['team_id'] : null;
        $data['category_id'] = isset($data['category_id']) && $data['category_id'] !== null ? (int) $data['category_id'] : null;

        // DISI-81: gestor no puede cambiar el equipo del atleta a uno que
        // no sea el suyo.
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->isGestor()) {
            $data['team_id'] = $user->team_id;
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

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', "Atleta «{$athlete->full_name}» actualizado correctamente.");
    }

    public function destroy(Athlete $athlete): RedirectResponse
    {
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
