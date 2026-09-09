<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $teams = Team::orderBy('name')
            ->withCount(['athletes', 'homeGames', 'awayGames'])
            ->paginate(15);

        return view('teams.index', compact('teams'));
    }

    public function create(): View
    {
        return view('teams.create');
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);

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
        $team->loadCount(['athletes', 'homeGames', 'awayGames']);
        $team->load(['athletes' => function ($q) {
            $q->orderBy('number')->limit(15);
        }]);

        return view('teams.show', compact('team'));
    }

    public function edit(Team $team): View
    {
        return view('teams.edit', compact('team'));
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $team->active);

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
