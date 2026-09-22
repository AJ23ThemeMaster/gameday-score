<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCoachRequest;
use App\Http\Requests\UpdateCoachRequest;
use App\Models\Coach;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD de entrenadores (coaches) por equipo.
 *
 * Rutas anidadas: teams/{team}/coaches/...
 * Permisos:
 *   - index/show: admin + gestor (gestor solo ve su equipo).
 *   - create/store/edit/update/destroy: admin + gestor (gestor solo
 *     puede gestionar coaches de SU equipo).
 *   - delegado NO entra a este modulo (su scope es atletas).
 */
class CoachController extends Controller
{
    public function __construct()
    {
        // Index/show/edit/update: admin + gestor (gestor con scope team).
        $this->middleware('admin_or_gestor')->except(['create', 'store', 'destroy']);
        // Create/store/destroy: admin + gestor (gestor con scope team).
        // El delegado NO puede gestionar coaches.
        $this->middleware('admin_or_gestor')->only(['create', 'store', 'destroy']);
    }

    /**
     * El gestor solo puede gestionar coaches de SU equipo.
     */
    private function authorizeGestorOnCoach(?Team $team): void
    {
        if (! $team) {
            abort(404);
        }
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->isGestor() && (int) $user->team_id !== (int) $team->id) {
            abort(403, 'Solo puedes administrar entrenadores del equipo al que estás asociado.');
        }
    }

    public function index(Team $team): View
    {
        $this->authorizeGestorOnCoach($team);
        $coaches = $team->coaches()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('coaches.index', compact('team', 'coaches'));
    }

    public function create(Team $team): View
    {
        $this->authorizeGestorOnCoach($team);

        return view('coaches.create', compact('team'));
    }

    public function store(StoreCoachRequest $request, Team $team): RedirectResponse
    {
        $this->authorizeGestorOnCoach($team);
        $data = $request->validated();
        $data['team_id'] = $team->id;
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo para PHP 8.4 strict types.
        $data['user_id'] = isset($data['user_id']) && $data['user_id'] !== null ? (int) $data['user_id'] : null;

        $coach = Coach::create($data);

        return redirect()
            ->route('teams.coaches.show', [$team, $coach])
            ->with('status', "Entrenador «{$coach->full_name}» creado correctamente.");
    }

    public function show(Team $team, Coach $coach): View
    {
        $this->authorizeGestorOnCoach($team);
        // Verificar que el coach pertenece al team de la ruta.
        if ((int) $coach->team_id !== (int) $team->id) {
            abort(404);
        }

        return view('coaches.show', compact('team', 'coach'));
    }

    public function edit(Team $team, Coach $coach): View
    {
        $this->authorizeGestorOnCoach($team);
        if ((int) $coach->team_id !== (int) $team->id) {
            abort(404);
        }

        return view('coaches.edit', compact('team', 'coach'));
    }

    public function update(UpdateCoachRequest $request, Team $team, Coach $coach): RedirectResponse
    {
        $this->authorizeGestorOnCoach($team);
        if ((int) $coach->team_id !== (int) $team->id) {
            abort(404);
        }
        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        $data['user_id'] = isset($data['user_id']) && $data['user_id'] !== null ? (int) $data['user_id'] : null;
        $coach->update($data);

        return redirect()
            ->route('teams.coaches.show', [$team, $coach])
            ->with('status', "Entrenador «{$coach->full_name}» actualizado correctamente.");
    }

    public function destroy(Team $team, Coach $coach): RedirectResponse
    {
        $this->authorizeGestorOnCoach($team);
        if ((int) $coach->team_id !== (int) $team->id) {
            abort(404);
        }
        $name = $coach->full_name;
        $coach->delete();

        return redirect()
            ->route('teams.coaches.index', $team)
            ->with('status', "Entrenador «{$name}» eliminado correctamente.");
    }
}
