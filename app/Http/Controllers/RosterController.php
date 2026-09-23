<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRosterRequest;
use App\Http\Requests\UpdateRosterRequest;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Coach;
use App\Models\Roster;
use App\Models\Team;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CRUD de Roster por (equipo, categoria).
 *
 * Ruta anidada: teams/{team}/rosters/...
 *
 * Permisos (middleware + helper):
 *  - admin: CRUD completo sobre cualquier roster.
 *  - gestor: solo rosters de SU equipo (chequeo via
 *    authorizeGestorOnRoster).
 *  - delegado: NO entra a este modulo (su scope es atletas).
 *
 * Cada roster agrupa:
 *  - manager (Coach con role libre, en roster.manager_coach_id).
 *  - N coaches adicionales (pivote roster_coach).
 *  - 1 delegado (User con rol 'delegado' y mismo team_id+category_id).
 *  - atletas: derivados de Athlete.team_id + Athlete.category_id
 *    (no requieren pivote; cualquier atleta con ese (team,category)
 *    pertenece automaticamente al roster).
 */
class RosterController extends Controller
{
    public function __construct()
    {
        // El delegado NO puede acceder a este modulo en absoluto.
        // admin y gestor (gestor con scope team) pueden CRUD.
        $this->middleware('admin_or_gestor');
    }

    /**
     * Garantiza que el gestor solo actua sobre rosters de SU equipo.
     */
    private function authorizeGestorOnRoster(?Team $team): void
    {
        if (! $team) {
            abort(404);
        }
        $user = Auth::user();
        if ($user && $user->isGestor() && (int) $user->team_id !== (int) $team->id) {
            abort(403, 'Solo puedes administrar rosters del equipo al que estás asociado.');
        }
    }

    /**
     * Verifica que el roster pertenece al team de la ruta (evita acceso
     * cruzando equipos via inyeccion de {roster}).
     */
    private function authorizeRosterBelongsToTeam(Team $team, Roster $roster): void
    {
        if ((int) $roster->team_id !== (int) $team->id) {
            abort(404);
        }
    }

    public function index(Team $team): View
    {
        $this->authorizeGestorOnRoster($team);

        // withCount(['athletes' => fn ($q) => ...]) no funciona directo
        // porque la relacion `athletes()` no incluye el filtro por
        // categoria del roster (ver comentario en App\Models\Roster).
        // Solucion: paginar manualmente y aplicar el count en PHP.
        $rosters = $team->rosters()
            ->with(['category', 'managerCoach', 'delegateUser'])
            ->withCount('coaches')
            ->orderBy('category_id')
            ->orderBy('name')
            ->paginate(20);

        // Hidratar counts de atletas por categoria para los rosters de la pagina actual.
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

        return view('rosters.index', compact('team', 'rosters'));
    }

    public function create(Team $team): View
    {
        $this->authorizeGestorOnRoster($team);

        // Todas las categorias activas. La vinculacion con el equipo es
        // a traves de los atletas (athletes.team_id + category_id) y de
        // la FK rosters.team_id; categories.team_id es nullable en BD.
        $categories = Category::where('active', true)->orderBy('name')->get();

        // Coaches del equipo (manager + adicionales).
        $coaches = $team->coaches()->orderBy('last_name')->orderBy('first_name')->get();

        // Delegados candidatos: users con rol 'delegado' y team_id = $team.
        $delegates = User::role('delegado')
            ->where('team_id', $team->id)
            ->whereNotNull('category_id')
            ->orderBy('name')
            ->get();

        $roster = new Roster(['active' => true]);

        return view('rosters.create', compact('team', 'categories', 'coaches', 'delegates', 'roster'));
    }

    public function store(StoreRosterRequest $request, Team $team): RedirectResponse
    {
        $this->authorizeGestorOnRoster($team);
        $data = $request->validated();

        // Cast defensivo para PHP 8.4 strict types.
        $data['team_id'] = $team->id;
        $data['category_id'] = (int) $data['category_id'];
        $data['manager_coach_id'] = isset($data['manager_coach_id']) && $data['manager_coach_id'] !== null
            ? (int) $data['manager_coach_id']
            : null;
        $data['delegate_user_id'] = isset($data['delegate_user_id']) && $data['delegate_user_id'] !== null
            ? (int) $data['delegate_user_id']
            : null;
        $data['active'] = $request->boolean('active', true);

        $coaches = $data['coaches'] ?? [];
        unset($data['coaches']);

        $roster = Roster::create($data);

        // Sync coaches adicionales (sin incluir al manager).
        $coachesToSync = collect($coaches)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $data['manager_coach_id'])
            ->unique()
            ->values()
            ->all();
        if ($coachesToSync) {
            $roster->coaches()->sync($coachesToSync);
        }

        return redirect()
            ->route('teams.rosters.show', [$team, $roster])
            ->with('status', "Roster «{$roster->full_name}» creado correctamente.");
    }

    public function show(Team $team, Roster $roster): View
    {
        $this->authorizeGestorOnRoster($team);
        $this->authorizeRosterBelongsToTeam($team, $roster);

        $roster->load([
            'category', 'managerCoach', 'delegateUser',
            'coaches' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
        ]);

        // Atletas del roster (filtrados por la categoria del roster).
        // Cargamos manualmente para evitar el bug de eager-loading con
        // closure sobre `$this->category_id` en la relacion `athletes()`
        // del modelo. Esto devuelve una Collection que pasamos a la vista
        // como `categoryAthletes` para que el partial la muestre.
        $categoryAthletes = $roster->categoryAthletes()
            ->orderBy('number')
            ->orderBy('last_name')
            ->get();

        return view('rosters.show', compact('team', 'roster', 'categoryAthletes'));
    }

    public function edit(Team $team, Roster $roster): View
    {
        $this->authorizeGestorOnRoster($team);
        $this->authorizeRosterBelongsToTeam($team, $roster);

        $categories = Category::where('active', true)->orderBy('name')->get();
        $coaches = $team->coaches()->orderBy('last_name')->orderBy('first_name')->get();

        // Delegados candidatos (incluyendo el actual aunque ya no tenga
        // la categoria, para no perder la asignacion al guardar).
        $delegates = User::role('delegado')
            ->where(function ($q) use ($team, $roster) {
                $q->where('team_id', $team->id)
                    ->orWhere('id', $roster->delegate_user_id);
            })
            ->whereNotNull('category_id')
            ->orderBy('name')
            ->get();

        $roster->load('coaches');

        return view('rosters.edit', compact('team', 'roster', 'categories', 'coaches', 'delegates'));
    }

    public function update(UpdateRosterRequest $request, Team $team, Roster $roster): RedirectResponse
    {
        $this->authorizeGestorOnRoster($team);
        $this->authorizeRosterBelongsToTeam($team, $roster);

        $data = $request->validated();
        $data['category_id'] = (int) $data['category_id'];
        $data['manager_coach_id'] = isset($data['manager_coach_id']) && $data['manager_coach_id'] !== null
            ? (int) $data['manager_coach_id']
            : null;
        $data['delegate_user_id'] = isset($data['delegate_user_id']) && $data['delegate_user_id'] !== null
            ? (int) $data['delegate_user_id']
            : null;
        $data['active'] = $request->boolean('active');

        $coaches = $data['coaches'] ?? [];
        unset($data['coaches']);

        $roster->update($data);

        $coachesToSync = collect($coaches)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $data['manager_coach_id'])
            ->unique()
            ->values()
            ->all();
        $roster->coaches()->sync($coachesToSync);

        return redirect()
            ->route('teams.rosters.show', [$team, $roster])
            ->with('status', "Roster «{$roster->full_name}» actualizado correctamente.");
    }

    public function destroy(Team $team, Roster $roster): RedirectResponse
    {
        $this->authorizeGestorOnRoster($team);
        $this->authorizeRosterBelongsToTeam($team, $roster);

        $name = $roster->full_name;
        // La migracion cascadeOnDelete limpia roster_coach; los atletas
        // NO se borran porque la relacion es derivada (no FK directa).
        $roster->delete();

        return redirect()
            ->route('teams.rosters.index', $team)
            ->with('status', "Roster «{$name}» eliminado correctamente.");
    }

    /**
     * Genera un PDF del roster (descargable).
     *
     * El PDF replica el formato usado tradicionalmente en la escuela:
     *  - Cabecera con logo del equipo + datos de la escuela + fecha.
     *  - Titulo "ROSTER." + categoria.
     *  - Tabla con atletas (N°, nombre, cedula, fecha de nacimiento).
     *  - Secciones finales: MANAGER, TECNICOS, DELEGADO.
     *
     * Los atletas se derivan por (team_id, category_id) automaticamente.
     */
    public function pdf(Team $team, Roster $roster): Response
    {
        $this->authorizeGestorOnRoster($team);
        $this->authorizeRosterBelongsToTeam($team, $roster);

        $roster->load([
            'category', 'managerCoach', 'delegateUser',
            'coaches' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
        ]);

        // El team necesita el nombre de la liga para el membrete.
        $team->loadMissing('league');

        // Atletas del roster filtrados por la categoria del roster.
        // En el PDF se ordenan por fecha de nacimiento (mas viejo arriba).
        // Usamos orderByRaw para que los atletas SIN birth_date caigan
        // al final del listado (no al principio, donde distorsionarian
        // el orden real de los demas).
        $athletes = $roster->categoryAthletes()
            ->orderByRaw('birth_date IS NOT NULL DESC')
            ->orderBy('birth_date')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Logo del equipo en base64 (DomPDF necesita imagenes embebidas
        // locales; pasamos base64 para que sea autonoma y no requiera
        // permisos especiales al filesystem).
        $logoBase64 = null;
        if ($team->logo_path) {
            $absolutePath = \Illuminate\Support\Facades\Storage::disk('public')->path($team->logo_path);
            if (is_file($absolutePath)) {
                $mime = 'image/'.strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
                if (str_contains($mime, 'jpg') || str_contains($mime, 'jpeg')) {
                    $mime = 'image/jpeg';
                }
                if (str_contains($mime, 'webp')) {
                    $mime = 'image/webp';
                }
                $logoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($absolutePath));
            }
        }

        // Si el delegado (User) tiene coach vinculado en el mismo equipo
        // con nombre similar, usamos los datos del coach (cédula +
        // birthdate) porque el modelo User no tiene esos campos. Caso
        // contrario, quedan vacíos en el PDF. La tabla `coaches` no
        // tiene `category_id` (solo `team_id`), asi que el filtro es
        // solo por team + nombre.
        $delegateCoachData = null;
        if ($roster->delegateUser) {
            $delegateName = $roster->delegateUser->name;
            $delegateCoachData = Coach::query()
                ->where('team_id', $team->id)
                ->where(function ($q) use ($delegateName) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) = ?", [$delegateName])
                      ->orWhere('first_name', $delegateName);
                })
                ->first();
        }

        $pdf = Pdf::loadView('rosters.pdf', [
            'team' => $team,
            'roster' => $roster,
            'category' => $roster->category,
            'athletes' => $athletes,
            'manager' => $roster->managerCoach,
            'coaches' => $roster->coaches,
            'delegateUser' => $roster->delegateUser,
            'delegateCoachData' => $delegateCoachData,
            'logoBase64' => $logoBase64,
            'today' => now(),
        ]);

        // Tamano carta, vertical, margenes estrechos.
        $pdf->setPaper('letter', 'portrait');
        $pdf->setOptions([
            'dpi' => 120,
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        $filename = 'Roster_'.preg_replace('/[^A-Za-z0-9_-]+/', '_', $team->name).'_'
            .preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $roster->category?->name).'.pdf';

        return $pdf->download($filename);
    }
}
