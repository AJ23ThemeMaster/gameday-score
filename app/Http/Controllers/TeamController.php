<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $teams = Team::with('tournament.league')
            ->orderBy('name')
            ->withCount(['athletes', 'homeGames', 'awayGames'])
            ->paginate(15);

        return view('teams.index', compact('teams'));
    }

    public function create(): View
    {
        $tournaments = Tournament::with('league')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('teams.create', compact('tournaments'));
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo para PHP 8.4 strict types
        $data['tournament_id'] = $data['tournament_id'] !== null ? (int) $data['tournament_id'] : null;

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
        $team->load(['tournament.league', 'athletes' => function ($q) {
            $q->orderBy('number')->limit(15);
        }]);
        $team->loadCount(['athletes', 'homeGames', 'awayGames', 'categories']);

        return view('teams.show', compact('team'));
    }

    public function edit(Team $team): View
    {
        $tournaments = Tournament::with('league')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('teams.edit', compact('team', 'tournaments'));
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $team->active);
        $data['tournament_id'] = $data['tournament_id'] !== null ? (int) $data['tournament_id'] : null;

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
