<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAthleteRequest;
use App\Http\Requests\UpdateAthleteRequest;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AthleteController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(): View
    {
        $athletes = Athlete::with(['team', 'category'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('athletes.index', compact('athletes'));
    }

    public function create(): View
    {
        $teams = Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('athletes.create', compact('teams', 'categories'));
    }

    public function store(StoreAthleteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo para PHP 8.4 strict types
        $data['team_id'] = isset($data['team_id']) && $data['team_id'] !== null ? (int) $data['team_id'] : null;
        $data['category_id'] = isset($data['category_id']) && $data['category_id'] !== null ? (int) $data['category_id'] : null;

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
        }

        if ($request->hasFile('document_file')) {
            $data['document_file_path'] = $request->file('document_file')->store('athletes/documents', 'public');
        }

        $athlete = Athlete::create($data);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', "Atleta «{$athlete->full_name}» creado correctamente.");
    }

    public function show(Athlete $athlete): View
    {
        // DISI-62: cargamos team + category + juegos del atleta (via game_athlete
        // pivot) y computamos stats agregadas + per-game en una sola pasada.
        $athlete->load(['team', 'category', 'team.league']);

        // Juegos en los que el atleta participo (como bateador o como pitcher).
        // game_athlete es la pivot con team_id / lineup_order / position / is_pitcher.
        $games = \App\Models\Game::query()
            ->whereHas('athletes', function ($q) use ($athlete) {
                $q->where('athletes.id', $athlete->id);
            })
            ->orderByDesc('scheduled_at')
            ->limit(50)
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->get();

        // Stats globales (todos los juegos del atleta)
        $careerBatting = \App\Models\Play::statsForBatter($athlete->id, $athlete->id);
        // (Play::statsForBatter espera (gameId, batterId) — usamos un shim
        //  para "todos los juegos" llamando directo a la query agregada.)
        $careerBatting = $this->aggregateBatterStats($athlete->id);
        $careerPitching = $this->aggregatePitcherStats($athlete->id);

        // Stats por juego (para la tabla)
        $perGame = [];
        foreach ($games as $g) {
            $b = \App\Models\Play::statsForBatter($g->id, $athlete->id);
            $p = \App\Models\Play::statsForPitcher($g->id, $athlete->id);
            $perGame[] = [
                'game' => $g,
                'batting' => $b,
                'pitching' => $p,
            ];
        }

        return view('athletes.show', compact('athlete', 'games', 'careerBatting', 'careerPitching', 'perGame'));
    }

    /**
     * DISI-62: stats de bateador AGREGADAS en todos los juegos del atleta
     * (no por juego). Es el equivalente de Play::statsForBatter pero sin
     * filtrar por game_id. Misma logica de conteo (hits, AB, K, BB, AVG).
     */
    private function aggregateBatterStats(int $athleteId): array
    {
        $plays = \App\Models\Play::where('batter_id', $athleteId)->get();
        $at_bats = 0;
        $hits = 0;
        $strikeouts = 0;
        $walks = 0;
        foreach ($plays as $p) {
            $type = $p->type;
            if ($type === \App\Models\Play::TYPE_HIT) {
                $at_bats++; $hits++;
            } elseif ($type === \App\Models\Play::TYPE_OUT) {
                // Strikeout cuenta como AB; otros outs (groundout, flyout, etc.)
                // tambien cuentan como AB (en baseball cualquier out no-K es AB).
                $at_bats++;
                if ($p->subtype === \App\Models\Play::SUBTYPE_OUT_STRIKEOUT) {
                    $strikeouts++;
                }
            } elseif ($type === \App\Models\Play::TYPE_WALK) {
                // Walk (base por bolas) NO cuenta como AB (no es un at-bat).
                $walks++;
            } elseif ($type === \App\Models\Play::TYPE_HBP) {
                // HBP tampoco cuenta como AB.
            } elseif ($type === \App\Models\Play::TYPE_BUNT) {
                // Sacrifice bunt / bunt out: NO cuenta como AB si es sacrifice;
                // SI cuenta como AB si es bunt out / bunt single.
                if ($p->subtype !== \App\Models\Play::SUBTYPE_BUNT_SACRIFICE) {
                    $at_bats++;
                    if ($p->subtype === \App\Models\Play::SUBTYPE_BUNT_SINGLE) {
                        $hits++;
                    }
                }
            }
        }
        $avg = $at_bats > 0 ? round($hits / $at_bats, 3) : 0.0;

        return compact('at_bats', 'hits', 'strikeouts', 'walks', 'avg');
    }

    /**
     * DISI-62: stats de pitcher AGREGADAS en todos los juegos del atleta.
     * Equivalente de Play::statsForPitcher sin filtrar por game_id.
     */
    private function aggregatePitcherStats(int $athleteId): array
    {
        $plays = \App\Models\Play::where('pitcher_id', $athleteId)->get();
        $pitches = 0;
        $strikes = 0;
        $balls = 0;
        $strikeouts = 0;
        $hits = 0;
        $walks = 0;
        foreach ($plays as $p) {
            $type = $p->type;
            $subtype = $p->subtype;
            if ($type === \App\Models\Play::TYPE_PITCH) {
                if ($subtype === 'at_bat_start') {
                    continue;
                }
                $pitches++;
                if ($subtype === 'ball') {
                    $balls++;
                } elseif (in_array($subtype, ['looking', 'swinging', 'foul_tip'], true)) {
                    $strikes++;
                }
            } elseif ($type === \App\Models\Play::TYPE_OUT && $subtype === \App\Models\Play::SUBTYPE_OUT_STRIKEOUT) {
                $strikeouts++;
            } elseif ($type === \App\Models\Play::TYPE_HIT) {
                $hits++;
            } elseif ($type === \App\Models\Play::TYPE_WALK) {
                $walks++;
            }
        }

        return compact('pitches', 'strikes', 'balls', 'strikeouts', 'hits', 'walks');
    }

    public function edit(Athlete $athlete): View
    {
        $teams = Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('athletes.edit', compact('athlete', 'teams', 'categories'));
    }

    public function update(UpdateAthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $athlete->active);
        $data['team_id'] = isset($data['team_id']) && $data['team_id'] !== null ? (int) $data['team_id'] : null;
        $data['category_id'] = isset($data['category_id']) && $data['category_id'] !== null ? (int) $data['category_id'] : null;

        if ($request->hasFile('photo')) {
            $this->deletePhoto($athlete);
            $data['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($athlete);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('document_file')) {
            $this->deleteDocument($athlete);
            $data['document_file_path'] = $request->file('document_file')->store('athletes/documents', 'public');
        } elseif ($request->boolean('remove_document')) {
            $this->deleteDocument($athlete);
            $data['document_file_path'] = null;
        }

        $athlete->update($data);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', "Atleta «{$athlete->full_name}» actualizado correctamente.");
    }

    public function destroy(Athlete $athlete): RedirectResponse
    {
        $name = $athlete->full_name;
        $this->deletePhoto($athlete);
        $this->deleteDocument($athlete);
        $athlete->delete();

        return redirect()
            ->route('athletes.index')
            ->with('status', "Atleta «{$name}» eliminado correctamente.");
    }

    private function deletePhoto(Athlete $athlete): void
    {
        if ($athlete->photo_path && Storage::disk('public')->exists($athlete->photo_path)) {
            Storage::disk('public')->delete($athlete->photo_path);
        }
    }

    private function deleteDocument(Athlete $athlete): void
    {
        if ($athlete->document_file_path && Storage::disk('public')->exists($athlete->document_file_path)) {
            Storage::disk('public')->delete($athlete->document_file_path);
        }
    }
}
