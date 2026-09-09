<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Models\Category;
use App\Models\Game;
use App\Models\Scorekeeper;
use App\Models\Stadium;
use App\Models\Team;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        $games = Game::with(['category', 'stadium', 'homeTeam', 'awayTeam'])
            ->ownedBy(Auth::id())
            ->orderByDesc('scheduled_at')
            ->paginate(15);

        $stats = [
            'total' => Game::ownedBy(Auth::id())->count(),
            'scheduled' => Game::ownedBy(Auth::id())->scheduled()->count(),
            'in_progress' => Game::ownedBy(Auth::id())->inProgress()->count(),
            'completed' => Game::ownedBy(Auth::id())->completed()->count(),
            'public' => Game::ownedBy(Auth::id())->public()->count(),
        ];

        return view('games.index', compact('games', 'stats'));
    }

    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $stadiums = Stadium::active()->orderBy('name')->get();
        $teams = Team::active()->orderBy('name')->get();
        $scorekeepers = Scorekeeper::active()->orderBy('last_name')->get();
        $referees = Referee::active()->orderBy('last_name')->get();
        $game = new Game();

        return view('games.create', compact('categories', 'stadiums', 'teams', 'scorekeepers', 'referees', 'game'));
    }

    public function store(StoreGameRequest $request): RedirectResponse
    {
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

        return redirect()
            ->route('games.show', $game)
            ->with('status', "Juego «{$game->homeTeam->name} vs {$game->awayTeam->name}» creado correctamente.");
    }

    public function show(Game $game): View
    {
        // Solo el owner puede ver sus juegos
        abort_unless($game->user_id === Auth::id(), 403);

        $game->load([
            'category', 'stadium', 'homeTeam', 'awayTeam', 'user',
            'scorekeepers', 'referees',
        ]);

        return view('games.show', compact('game'));
    }

    public function edit(Game $game): View
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $categories = Category::active()->orderBy('name')->get();
        $stadiums = Stadium::active()->orderBy('name')->get();
        $teams = Team::active()->orderBy('name')->get();
        $scorekeepers = Scorekeeper::active()->orderBy('last_name')->get();
        $referees = Referee::active()->orderBy('last_name')->get();

        $game->load(['scorekeepers', 'referees']);

        return view('games.edit', compact('game', 'categories', 'stadiums', 'teams', 'scorekeepers', 'referees'));
    }

    public function update(UpdateGameRequest $request, Game $game): RedirectResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

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
        abort_unless($game->user_id === Auth::id(), 403);

        $game->delete();

        return redirect()
            ->route('games.index')
            ->with('status', 'Juego eliminado correctamente.');
    }

    private function syncStaff(Game $game, array $data): void
    {
        $scorekeeperIds = $data['scorekeeper_ids'] ?? [];
        $refereeIds = $data['referee_ids'] ?? [];

        // sync() con pivot 'role' por defecto
        $game->scorekeepers()->sync($scorekeeperIds);
        $game->referees()->sync($refereeIds);
    }
}
