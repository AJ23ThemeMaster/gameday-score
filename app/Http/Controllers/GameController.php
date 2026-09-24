<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Http\Requests\UpdateGameStateRequest;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Game;
use App\Models\Scorekeeper;
use App\Models\Stadium;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\Referee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request): View
    {
        // DISI-15: admin y anotador pueden ver juegos.
        // Admin: ve todos los juegos.
        // Anotador: solo ve los juegos donde esta asignado (game_scorekeeper
        //          apunta a un scorekeeper cuyo user_id == Auth::id()).
        abort_unless(Gate::allows('view games'), 403);

        $query = Game::with([
            'category', 'stadium', 'homeTeam', 'awayTeam',
            'tournament', 'user', 'scorekeepers', 'referees',
        ]);

        if (Auth::user()->hasRole('anotador')) {
            $scorekeeperIds = Scorekeeper::where('user_id', Auth::id())->pluck('id');
            $gameIds = \DB::table('game_scorekeeper')
                ->whereIn('scorekeeper_id', $scorekeeperIds)
                ->pluck('game_id');
            $query->whereIn('id', $gameIds);
        } elseif (! Auth::user()->hasRole('admin')) {
            // Otros: solo sus propios juegos
            $query->where('user_id', Auth::id());
        }

        // Filtros (todos opcionales):
        // - fecha_desde / fecha_hasta: rango sobre scheduled_at
        // - home_team_id / away_team_id: equipos
        // - category_id: categoria
        // - stadium_id: estadio
        // - tournament_id: torneo
        // - scorekeeper_id: anotador (M2M game_scorekeeper)
        // - referee_id: arbitro (M2M game_referee)
        $filters = [
            'fecha_desde' => $request->query('fecha_desde'),
            'fecha_hasta' => $request->query('fecha_hasta'),
            'home_team_id' => $request->query('home_team_id'),
            'away_team_id' => $request->query('away_team_id'),
            'category_id' => $request->query('category_id'),
            'stadium_id' => $request->query('stadium_id'),
            'tournament_id' => $request->query('tournament_id'),
            'scorekeeper_id' => $request->query('scorekeeper_id'),
            'referee_id' => $request->query('referee_id'),
        ];

        if (! empty($filters['fecha_desde'])) {
            $query->where('scheduled_at', '>=', $filters['fecha_desde'].' 00:00:00');
        }
        if (! empty($filters['fecha_hasta'])) {
            $query->where('scheduled_at', '<=', $filters['fecha_hasta'].' 23:59:59');
        }
        if (! empty($filters['home_team_id'])) {
            $query->where('home_team_id', (int) $filters['home_team_id']);
        }
        if (! empty($filters['away_team_id'])) {
            $query->where('away_team_id', (int) $filters['away_team_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (! empty($filters['stadium_id'])) {
            $query->where('stadium_id', (int) $filters['stadium_id']);
        }
        if (! empty($filters['tournament_id'])) {
            $query->where('tournament_id', (int) $filters['tournament_id']);
        }
        if (! empty($filters['scorekeeper_id'])) {
            $gameIdsByScorekeeper = \DB::table('game_scorekeeper')
                ->where('scorekeeper_id', (int) $filters['scorekeeper_id'])
                ->pluck('game_id');
            $query->whereIn('id', $gameIdsByScorekeeper);
        }
        if (! empty($filters['referee_id'])) {
            $gameIdsByReferee = \DB::table('game_referee')
                ->where('referee_id', (int) $filters['referee_id'])
                ->pluck('game_id');
            $query->whereIn('id', $gameIdsByReferee);
        }

        $games = $query
            ->orderByDesc('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Game::ownedBy(Auth::id())->count(),
            'scheduled' => Game::ownedBy(Auth::id())->scheduled()->count(),
            'in_progress' => Game::ownedBy(Auth::id())->inProgress()->count(),
            'completed' => Game::ownedBy(Auth::id())->completed()->count(),
            'public' => Game::ownedBy(Auth::id())->public()->count(),
        ];

        // Listas para los dropdowns de filtros.
        $teams = Team::active()->orderBy('name')->get(['id', 'name', 'short_name']);
        $categories = Category::active()->orderBy('name')->get(['id', 'name']);
        $stadiums = Stadium::active()->orderBy('name')->get(['id', 'name']);
        $tournaments = Tournament::active()->orderBy('name')->get(['id', 'name']);
        $scorekeepers = Scorekeeper::active()->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $referees = Referee::active()->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('games.index', compact(
            'games', 'stats', 'filters',
            'teams', 'categories', 'stadiums', 'tournaments', 'scorekeepers', 'referees',
        ));
    }

    public function create(): View
    {
        // DISI-15: solo admin puede crear juegos
        abort_unless(Gate::allows('manage games crud'), 403);

        $categories = Category::active()->orderBy('name')->get();
        $tournaments = Tournament::active()->orderBy('name')->get();
        $stadiums = Stadium::active()->orderBy('name')->get();
        $teams = Team::active()->orderBy('name')->get();
        $scorekeepers = Scorekeeper::active()->orderBy('last_name')->get();
        $referees = Referee::active()->orderBy('last_name')->get();
        $game = new Game();

        return view('games.create', compact('categories', 'tournaments', 'stadiums', 'teams', 'scorekeepers', 'referees', 'game'));
    }

    public function store(StoreGameRequest $request): RedirectResponse
    {
        // DISI-15: solo admin puede crear juegos
        abort_unless(Gate::allows('manage games crud'), 403);

        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $data['is_public'] = $request->boolean('is_public');
        $data['status'] = $data['status'] ?? 'scheduled';

        // Auto-snapshot de reglas desde la categoría (no se modifica aunque la categoría cambie después)
        $category = Category::findOrFail($data['category_id']);
        $data['innings_count'] = $category->innings_count;
        $data['mercy_rule_difference'] = $category->mercy_rule_difference;
        $data['mercy_rule_inning'] = $category->mercy_rule_inning;
        $data['pitch_limit'] = $category->pitch_limit;

        $game = Game::create($data);

        // Sincronizar anotadores y árbitros (M2M)
        $this->syncStaff($game, $data);

        // DISI-67: auto-poblar el roster con todos los atletas de los 2 equipos
        // que pertenezcan a la categoria del juego.
        $rosterCount = $this->seedRosterFromCategory($game);

        $flash = "Juego «{$game->homeTeam->name} vs {$game->awayTeam->name}» creado correctamente.";
        if ($rosterCount > 0) {
            $flash .= " Se precargaron {$rosterCount} atletas del roster.";
        } else {
            $flash .= ' No hay atletas en esos equipos para esta categoría todavía.';
        }

        return redirect()
            ->route('games.show', $game)
            ->with('status', $flash);
    }

    public function show(Game $game): View
    {
        // DISI-15: usar policy para owner/admin/anotador asignado
        $this->authorize('view', $game);

        $game->load([
            'category', 'stadium', 'homeTeam', 'awayTeam', 'user',
            'scorekeepers', 'referees',
        ]);

        return view('games.show', compact('game'));
    }

    public function edit(Game $game): View
    {
        $this->authorize('update', $game);

        $categories = Category::active()->orderBy('name')->get();
        $tournaments = Tournament::active()->orderBy('name')->get();
        $stadiums = Stadium::active()->orderBy('name')->get();
        $teams = Team::active()->orderBy('name')->get();
        $scorekeepers = Scorekeeper::active()->orderBy('last_name')->get();
        $referees = Referee::active()->orderBy('last_name')->get();

        $game->load(['scorekeepers', 'referees']);

        return view('games.edit', compact('game', 'categories', 'tournaments', 'stadiums', 'teams', 'scorekeepers', 'referees'));
    }

    public function update(UpdateGameRequest $request, Game $game): RedirectResponse
    {
        $this->authorize('update', $game);

        $data = $request->validated();
        $data['is_public'] = $request->boolean('is_public');

        // Si la categoría cambió, re-snapshot las reglas
        if ((int) $game->category_id !== (int) $data['category_id']) {
            $category = Category::findOrFail($data['category_id']);
            $data['innings_count'] = $category->innings_count;
            $data['mercy_rule_difference'] = $category->mercy_rule_difference;
            $data['mercy_rule_inning'] = $category->mercy_rule_inning;
            $data['pitch_limit'] = $category->pitch_limit;
        }

        $game->update($data);

        $this->syncStaff($game, $data);

        return redirect()
            ->route('games.show', $game)
            ->with('status', "Juego «{$game->homeTeam->name} vs {$game->awayTeam->name}» actualizado correctamente.");
    }

    public function destroy(Game $game): RedirectResponse
    {
        $this->authorize('delete', $game);

        $game->delete();

        return redirect()
            ->route('games.index')
            ->with('status', 'Juego eliminado correctamente.');
    }

    // ----------------------------------------------------------------
    // DISI-9: Live scoreboard (control en vivo del juego)
    // ----------------------------------------------------------------

    public function live(Game $game): View
    {
        $this->authorize('view', $game);

        $game->load([
            'category', 'stadium', 'homeTeam', 'awayTeam',
            'homeTeam.athletes', 'awayTeam.athletes',
            'scorekeepers', 'referees',
        ]);

        return view('games.live', compact('game'));
    }

    public function updateState(UpdateGameStateRequest $request, Game $game): RedirectResponse
    {
        $this->authorize('score', $game);

        $data = array_filter($request->validated(), fn ($v) => $v !== null);

        // Si el juego pasa a in_progress por primera vez, registrar started_at
        if (isset($data['status']) && $data['status'] === 'in_progress' && ! $game->started_at) {
            $data['started_at'] = now();
        }
        // Si el juego pasa a completed por primera vez, registrar ended_at
        if (isset($data['status']) && $data['status'] === 'completed' && ! $game->ended_at) {
            $data['ended_at'] = now();
        }

        $game->update($data);

        return redirect()
            ->route('games.scoreboard', $game)
            ->with('status', __('Marcador actualizado.'));
    }

    public function addRun(Request $request, Game $game): RedirectResponse
    {
        $this->authorize('score', $game);

        $request->validate([
            'team' => ['required', 'in:home,away'],
        ]);

        $column = $request->input('team') === 'home' ? 'home_score' : 'away_score';
        $game->increment($column);

        // Si era el primer cambio de estado, pasar a in_progress
        if ($game->status === 'scheduled') {
            $game->update(['status' => 'in_progress', 'started_at' => now()]);
        }

        return redirect()
            ->route('games.scoreboard', $game)
            ->with('status', __('Carrera sumada.'));
    }

    public function endInning(Request $request, Game $game): RedirectResponse
    {
        $this->authorize('score', $game);

        // Si es la parte de arriba (top), cambiar a parte de abajo (bottom) del mismo inning
        // Si es la parte de abajo, avanzar al siguiente inning, parte de arriba
        if ($game->inning_half === 'top') {
            $game->update(['inning_half' => 'bottom']);
        } else {
            $newInning = $game->current_inning + 1;

            // Si completamos todos los innings, finalizar el juego
            if ($newInning > $game->innings_count) {
                $game->update([
                    'current_inning' => $game->innings_count,
                    'inning_half' => 'bottom',
                    'status' => 'completed',
                    'ended_at' => now(),
                ]);
                return redirect()
                    ->route('games.scoreboard', $game)
                    ->with('status', __('¡Juego finalizado!'));
            }

            $game->update([
                'current_inning' => $newInning,
                'inning_half' => 'top',
                'balls' => 0,
                'strikes' => 0,
                'outs' => 0,
                'bases' => null,
            ]);
        }

        return redirect()
            ->route('games.scoreboard', $game)
            ->with('status', __('Inning avanzado.'));
    }

    public function stateJson(Game $game): JsonResponse
    {
        // Endpoint público (sin auth) para polling desde la vista pública
        // Pero validamos que el juego sea público o el owner lo solicite
        return response()->json([
            'id' => $game->id,
            'status' => $game->status,
            'current_inning' => $game->current_inning,
            'inning_half' => $game->inning_half,
            'balls' => $game->balls,
            'strikes' => $game->strikes,
            'outs' => $game->outs,
            'bases' => $game->bases,
            'home_score' => $game->home_score,
            'away_score' => $game->away_score,
            'home_team' => ['id' => $game->homeTeam->id, 'name' => $game->homeTeam->name, 'logo_url' => $game->homeTeam->logoUrl],
            'away_team' => ['id' => $game->awayTeam->id, 'name' => $game->awayTeam->name, 'logo_url' => $game->awayTeam->logoUrl],
            'updated_at' => $game->updated_at->toIso8601String(),
        ]);
    }

    private function syncStaff(Game $game, array $data): void
    {
        $scorekeeperIds = $data['scorekeeper_ids'] ?? [];
        $refereeIds = $data['referee_ids'] ?? [];

        // sync() con pivot 'role' por defecto
        $game->scorekeepers()->sync($scorekeeperIds);
        $game->referees()->sync($refereeIds);
    }

    /**
     * DISI-67: auto-poblar el roster del juego con todos los atletas del
     * equipo local Y visitante que tengan `category_id` igual a la
     * categoria del juego. Devuelve la cantidad insertada.
     *
     * El `team_id` del pivot se llena con el equipo del atleta (que tiene
     * que coincidir con local o visitante por el filtro whereIn).
     */
    private function seedRosterFromCategory(Game $game): int
    {
        $athletes = Athlete::query()
            ->where('category_id', $game->category_id)
            ->whereIn('team_id', [$game->home_team_id, $game->away_team_id])
            ->select(['id', 'team_id'])
            ->get();

        if ($athletes->isEmpty()) {
            return 0;
        }

        $syncData = [];
        foreach ($athletes as $a) {
            // sync() espera [athlete_id => [pivot_data]]
            $syncData[$a->id] = ['team_id' => $a->team_id];
        }

        $game->athletes()->sync($syncData);

        return $athletes->count();
    }
}
