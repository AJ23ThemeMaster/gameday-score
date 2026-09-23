<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\League;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct()
    {
        // DISI-81 + DISI-XXX: gestor puede editar y ver SU equipo.
        // El chequeo fino se hace dentro de cada metodo.
        // - index: admin_or_gestor (gestor solo ve su equipo, filtrado en el metodo)
        // - create/store: admin-only (gestor NO crea equipos nuevos;
        //   admin es quien crea y luego asigna via User::team_id)
        // - edit/update: admin_or_gestor + authorizeGestorOnTeam
        // - destroy: admin-only (decision del usuario: borrar equipo es
        //   atribucion exclusiva del admin porque tiene implicaciones
        //   de integridad referencial con juegos, atletas y cascadas).
        $this->middleware('admin_or_gestor')->except(['show']);
        $this->middleware('admin')->only(['create', 'store', 'destroy']);
    }

    /**
     * DISI-81: gestor solo puede editar SU equipo asociado. Admin edita cualquiera.
     */
    private function authorizeGestorOnTeam(Team $team): void
    {
        $user = Auth::user();
        if ($user && $user->isGestor() && ! $user->isGestorOwning($team)) {
            abort(403, 'Solo puedes administrar el equipo al que estás asociado.');
        }
    }

    public function index(): View
    {
        $user = Auth::user();

        // DISI-XXX: gestor solo ve SU equipo asociado. Admin ve todos.
        $query = Team::with('league')
            ->orderBy('name')
            ->withCount(['athletes', 'homeGames', 'awayGames', 'categories']);

        if ($user && $user->isGestor()) {
            $query->where('id', $user->team_id);
        }

        $teams = $query->paginate(15)->withQueryString();

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
        // El show del equipo muestra ahora una vista compacta: info del
        // equipo + lista de rosters por categoria. La tabla plana de
        // atletas con filtros (DISI-64) se elimino porque ese detalle
        // pertenece al show de cada roster (que ya filtra por categoria
        // y permite gestionarlo).
        $team->load([
            'league',
            'tournaments' => fn ($q) => $q->orderBy('name'),
        ]);
        $team->loadCount(['athletes', 'homeGames', 'awayGames', 'categories', 'tournaments', 'coaches']);

        // DISI-roster: rosters del equipo por categoria, agrupados para
        // mostrarlos como una grilla compacta en teams.show.
        // El conteo de atletas se hace en PHP (no via withCount) porque
        // la relacion `athletes()` del modelo Roster no incluye el
        // filtro por categoria del roster (ver Roster.php).
        $rosters = $team->rosters()
            ->with(['category', 'managerCoach', 'delegateUser'])
            ->withCount('coaches')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $rosterIds = $rosters->pluck('id')->all();
        if ($rosterIds) {
            $athleteCounts = \App\Models\Athlete::query()
                ->selectRaw('rosters.id as roster_id, COUNT(athletes.id) as athletes_count')
                ->join('rosters', function ($join) {
                    $join->on('rosters.team_id', '=', 'athletes.team_id')
                         ->whereColumn('rosters.category_id', 'athletes.category_id');
                })
                ->whereIn('rosters.id', $rosterIds)
                ->groupBy('rosters.id')
                ->pluck('athletes_count', 'roster_id')
                ->all();

            foreach ($rosters as $r) {
                $r->athletes_count = $athleteCounts[$r->id] ?? 0;
            }
        }

        return view('teams.show', compact('team', 'rosters'));
    }

    public function edit(Team $team): View
    {
        $this->authorizeGestorOnTeam($team);
        $leagues = League::where('active', true)
            ->orderBy('name')
            ->get();

        return view('teams.edit', compact('team', 'leagues'));
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->authorizeGestorOnTeam($team);
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
        // DISI-XXX: destroy es admin-only (decision del usuario). El chequeo
        // authorizeGestorOnTeam queda como defense-in-depth: si en el
        // futuro alguien relaja el middleware, el chequeo interno sigue
        // bloqueando al gestor.
        $this->authorizeGestorOnTeam($team);

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
