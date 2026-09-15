<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\League;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(): View
    {
        $teams = Team::with('league')
            ->orderBy('name')
            ->withCount(['athletes', 'homeGames', 'awayGames', 'categories'])
            ->paginate(15);

        return view('teams.index', compact('teams'));
    }

    public function create(): View
    {
        $leagues = League::where('active', true)
            ->orderBy('name')
            ->get();

        return view('teams.create', compact('leagues'));
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo para PHP 8.4 strict types
        $data['league_id'] = $data['league_id'] !== null ? (int) $data['league_id'] : null;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('teams/logos', 'public');
        }

        $team = Team::create($data);

        return redirect()
            ->route('teams.show', $team)
            ->with('status', "Equipo «{$team->name}» creado correctamente.");
    }

    public function show(Team $team): View
    {
        // DISI-60: cargamos categorias (con conteo de atletas y juegos cada una)
        // y los torneos en los que el equipo participa (via pivot tournament_team).
        $team->load([
            'league',
            'athletes' => function ($q) {
                $q->orderBy('number')->limit(15);
            },
            'categories' => function ($q) {
                $q->orderBy('name');
            },
            'tournaments' => function ($q) {
                $q->orderBy('name');
            },
        ]);
        $team->loadCount(['athletes', 'homeGames', 'awayGames', 'categories', 'tournaments']);

        // Conteos por categoria (atletas + juegos)
        $categoryStats = [];
        foreach ($team->categories as $c) {
            $categoryStats[$c->id] = [
                'athletes' => $c->athletes()->count(),
                'games' => $c->games()->count(),
            ];
        }

        return view('teams.show', compact('team', 'categoryStats'));
    }

    public function edit(Team $team): View
    {
        $leagues = League::where('active', true)
            ->orderBy('name')
            ->get();

        return view('teams.edit', compact('team', 'leagues'));
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $team->active);
        $data['league_id'] = $data['league_id'] !== null ? (int) $data['league_id'] : null;

        if ($request->hasFile('logo')) {
            $this->deleteLogo($team);
            $data['logo_path'] = $request->file('logo')->store('teams/logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($team);
            $data['logo_path'] = null;
        }

        $team->update($data);

        return redirect()
            ->route('teams.show', $team)
            ->with('status', "Equipo «{$team->name}» actualizado correctamente.");
    }

    public function destroy(Team $team): RedirectResponse
    {
        if ($team->homeGames()->exists() || $team->awayGames()->exists()) {
            return redirect()
                ->route('teams.index')
                ->with('error', "No se puede eliminar el equipo «{$team->name}» porque tiene juegos asociados.");
        }

        $name = $team->name;
        $this->deleteLogo($team);
        $team->delete();

        return redirect()
            ->route('teams.index')
            ->with('status', "Equipo «{$name}» eliminado correctamente.");
    }

    private function deleteLogo(Team $team): void
    {
        if ($team->logo_path && Storage::disk('public')->exists($team->logo_path)) {
            Storage::disk('public')->delete($team->logo_path);
        }
    }
}
