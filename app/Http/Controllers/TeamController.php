<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\League;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(Request $request, Team $team): View
    {
        // DISI-64: el roster ahora es un listado FILTRABLE (Nombre | N° | Pos. | Estado | Categoria).
        // Las "Categorias del equipo" se siguen mostrando pero como chips que aplican
        // el filtro directamente al roster (en lugar de saltar al show global de la
        // categoria, que ignora el ambito del equipo).
        $team->load([
            'league',
            'tournaments' => fn ($q) => $q->orderBy('name'),
        ]);
        $team->loadCount(['athletes', 'homeGames', 'awayGames', 'categories', 'tournaments']);

        // Filtros del roster (todos opcionales)
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'number' => trim((string) $request->query('number', '')),
            'position' => trim((string) $request->query('position', '')),
            'status' => (string) $request->query('status', 'all'),
            'category_id' => $request->query('category_id'),
        ];
        $filters['status'] = in_array($filters['status'], ['active', 'inactive'], true) ? $filters['status'] : 'all';
        $filters['category_id'] = is_numeric($filters['category_id']) ? (int) $filters['category_id'] : null;

        $athleteQuery = Athlete::query()->where('team_id', $team->id);

        if ($filters['name'] !== '') {
            $athleteQuery->where(function ($q) use ($filters) {
                $like = '%'.$filters['name'].'%';
                $q->where('first_name', 'like', $like)
                  ->orWhere('last_name', 'like', $like);
            });
        }
        if ($filters['number'] !== '' && is_numeric($filters['number'])) {
            $athleteQuery->where('number', (int) $filters['number']);
        }
        if ($filters['position'] !== '') {
            $athleteQuery->where('position', $filters['position']);
        }
        if ($filters['status'] === 'active') {
            $athleteQuery->where('active', true);
        } elseif ($filters['status'] === 'inactive') {
            $athleteQuery->where('active', false);
        }
        if ($filters['category_id'] !== null) {
            $athleteQuery->where('category_id', $filters['category_id']);
        }

        $athletes = $athleteQuery
            ->with('category')
            ->orderBy('number')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $totalAthletes = Athlete::where('team_id', $team->id)->count();
        $filteredCount = $athletes->count();

        // DISI-64: categorias donde el equipo TIENE presencia de atletas
        // (agregadas desde athletes.category_id, no desde Category.team_id).
        // Esto resuelve el caso del team con 83 atletas pero 0 categorias
        // a nivel de Category (la columna Category.team_id nunca se lleno).
        $teamCategories = Category::query()
            ->whereIn('id', Athlete::where('team_id', $team->id)->whereNotNull('category_id')->pluck('category_id'))
            ->orderBy('name')
            ->get();

        $categoryStats = [];
        foreach ($teamCategories as $c) {
            $categoryStats[$c->id] = [
                'athletes' => Athlete::where('team_id', $team->id)->where('category_id', $c->id)->count(),
                'games' => $c->games()->count(),
            ];
        }

        // Posiciones presentes en el roster (para el dropdown del filtro)
        $positions = Athlete::where('team_id', $team->id)
            ->whereNotNull('position')
            ->distinct()
            ->orderBy('position')
            ->pluck('position');

        // Categorias disponibles para el dropdown del filtro (mismas que los chips)
        $filterCategories = $teamCategories;

        return view('teams.show', compact(
            'team', 'athletes', 'totalAthletes', 'filteredCount',
            'filters', 'teamCategories', 'categoryStats', 'positions', 'filterCategories'
        ));
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
