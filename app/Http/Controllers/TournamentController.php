<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTournamentRequest;
use App\Http\Requests\UpdateTournamentRequest;
use App\Models\League;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function index(): View
    {
        $tournaments = Tournament::with('league')
            ->orderBy('name')
            ->withCount('games')
            ->paginate(15);

        return view('tournaments.index', compact('tournaments'));
    }

    public function create(): View
    {
        $leagues = League::orderBy('name')->where('active', true)->get();
        $tournament = new Tournament();

        return view('tournaments.create', compact('leagues', 'tournament'));
    }

    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo: league_id a int (PHP 8.4 strict types)
        $data['league_id'] = (int) $data['league_id'];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')
                ->store('tournaments', 'public');
        }

        $tournament = Tournament::create($data);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', "Torneo «{$tournament->name}» creado correctamente.");
    }

    public function show(Tournament $tournament): View
    {
        $tournament->load(['league', 'games' => function ($q) {
            $q->latest('scheduled_at')->limit(10);
        }]);
        $tournament->loadCount('games');

        return view('tournaments.show', compact('tournament'));
    }

    public function edit(Tournament $tournament): View
    {
        $leagues = League::orderBy('name')->where('active', true)->get();

        return view('tournaments.edit', compact('tournament', 'leagues'));
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $tournament->active);
        // Cast defensivo: league_id a int (PHP 8.4 strict types)
        $data['league_id'] = (int) $data['league_id'];

        if ($request->boolean('remove_logo') && $tournament->logo_path) {
            Storage::disk('public')->delete($tournament->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($tournament->logo_path) {
                Storage::disk('public')->delete($tournament->logo_path);
            }
            $data['logo_path'] = $request->file('logo')
                ->store('tournaments', 'public');
        }

        $tournament->update($data);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', "Torneo «{$tournament->name}» actualizado correctamente.");
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        if ($tournament->games()->exists()) {
            return redirect()
                ->route('tournaments.index')
                ->with('error', "No se puede eliminar el torneo «{$tournament->name}» porque tiene juegos asociados.");
        }

        if ($tournament->logo_path) {
            Storage::disk('public')->delete($tournament->logo_path);
        }

        $name = $tournament->name;
        $tournament->delete();

        return redirect()
            ->route('tournaments.index')
            ->with('status', "Torneo «{$name}» eliminado correctamente.");
    }
}
