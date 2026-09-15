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
    public function __construct()
    {
        $this->middleware('admin');
    }

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
        // DISI-57: teams se pasan vacios al create; el form los lista via AJAX
        // cuando el usuario selecciona la liga.
        $availableTeams = collect();

        return view('tournaments.create', compact('leagues', 'tournament', 'availableTeams'));
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

        // DISI-57: sincronizar equipos del torneo. Solo aceptamos ids que
        // pertenezcan a la misma liga (regla de Frank: 1 liga = 1 ambito).
        $teamIds = $this->validTeamIdsForLeague($request, $tournament);
        $tournament->teams()->sync($teamIds);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', "Torneo «{$tournament->name}» creado correctamente.");
    }

    public function show(Tournament $tournament): View
    {
        $tournament->load([
            'league',
            'teams' => function ($q) { $q->orderBy('name'); },
            'games' => function ($q) {
                $q->latest('scheduled_at')->limit(10);
            },
        ]);
        $tournament->loadCount(['games', 'teams']);

        return view('tournaments.show', compact('tournament'));
    }

    public function edit(Tournament $tournament): View
    {
        $leagues = League::orderBy('name')->where('active', true)->get();
        // DISI-57: pasamos los equipos disponibles del mismo league que el torneo,
        // ya marcados los que actualmente estan asociados al torneo.
        $availableTeams = \App\Models\Team::where('league_id', $tournament->league_id)
            ->orderBy('name')
            ->get();

        return view('tournaments.edit', compact('tournament', 'leagues', 'availableTeams'));
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

        // DISI-57: sincronizar la lista de equipos del torneo. Si la liga
        // cambio, los teams viejos se quitan automaticamente (porque ya no
        // estan en el listado de teams disponibles).
        $teamIds = $this->validTeamIdsForLeague($request, $tournament);
        $tournament->teams()->sync($teamIds);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', "Torneo «{$tournament->name}» actualizado correctamente.");
    }

    /**
     * DISI-57: filtra los ids de equipos recibidos del form contra los que
     * pertenecen al league del torneo. Si llega un id de un team de otra
     * liga, lo descartamos silenciosamente (defensa contra tampering del
     * form). Devuelve array de ints.
     */
    private function validTeamIdsForLeague(\Illuminate\Http\Request $request, Tournament $tournament): array
    {
        $raw = $request->input('team_ids', []);
        if (! is_array($raw)) {
            return [];
        }
        $ints = array_map('intval', $raw);

        return \App\Models\Team::whereIn('id', $ints)
            ->where('league_id', $tournament->league_id)
            ->pluck('id')
            ->all();
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
