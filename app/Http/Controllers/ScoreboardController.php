<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Play;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScoreboardController extends Controller
{
    /**
     * Scoreboard principal (vista del anotador). Reemplaza al antiguo games.live
     * con un layout moderno: logos, scores, rombo con corredores, info del
     * pitcher/batter, count (B/S/O), y 3 tabs de acciones (PITCHEo / BATEo / EXTRAS).
     */
    public function show(Request $request, Game $game): View|JsonResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $game->load([
            'category', 'stadium', 'homeTeam', 'awayTeam',
            'homeTeam.athletes', 'awayTeam.athletes',
        ]);

        $state = Play::currentState($game->id);
        $score = Play::scoreboard($game->id);

        $pitcher = $state['current_pitcher_id']
            ? \App\Models\Athlete::find($state['current_pitcher_id'])
            : null;
        $batter = $state['current_batter_id']
            ? \App\Models\Athlete::find($state['current_batter_id'])
            : null;
        $onDeck = $this->onDeck($game, $state);
        $runners = $this->runners($state);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'state' => $state,
                'score' => $score,
                'pitcher' => $pitcher ? [
                    'id' => $pitcher->id,
                    'name' => $pitcher->full_name,
                    'number' => $pitcher->number,
                ] : null,
                'batter' => $batter ? [
                    'id' => $batter->id,
                    'name' => $batter->full_name,
                    'number' => $batter->number,
                ] : null,
                'on_deck' => $onDeck,
                'runners' => $runners,
            ]);
        }

        return view('games.scoreboard', compact(
            'game', 'state', 'score', 'pitcher', 'batter', 'onDeck', 'runners'
        ));
    }

    /**
     * Live poll endpoint: solo el state JSON, no la vista completa.
     * El cliente lo llama cada 5s para mantener el scoreboard sincronizado.
     */
    public function poll(Request $request, Game $game): JsonResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $state = Play::currentState($game->id);
        $score = Play::scoreboard($game->id);

        $pitcher = $state['current_pitcher_id']
            ? \App\Models\Athlete::find($state['current_pitcher_id'])
            : null;
        $batter = $state['current_batter_id']
            ? \App\Models\Athlete::find($state['current_batter_id'])
            : null;

        return response()->json([
            'state' => $state,
            'score' => $score,
            'pitcher' => $pitcher ? [
                'id' => $pitcher->id,
                'name' => $pitcher->full_name,
                'number' => $pitcher->number,
                'team_id' => $pitcher->team?->id,
            ] : null,
            'batter' => $batter ? [
                'id' => $batter->id,
                'name' => $batter->full_name,
                'number' => $batter->number,
            ] : null,
            'runners' => $this->runners($state),
            'on_deck' => $this->onDeck($game, $state),
        ]);
    }

    /**
     * Devuelve los atletas en base: 1B, 2B, 3B.
     */
    private function runners(array $state): array
    {
        $ids = array_filter([
            'first' => $state['bases']['first'] ?? null,
            'second' => $state['bases']['second'] ?? null,
            'third' => $state['bases']['third'] ?? null,
        ]);

        $out = [];
        foreach ($ids as $base => $athleteId) {
            $a = \App\Models\Athlete::find($athleteId);
            if ($a) {
                $out[$base] = [
                    'id' => $a->id,
                    'name' => $a->full_name,
                    'number' => $a->number,
                ];
            }
        }

        return $out;
    }

    /**
     * Devuelve el siguiente bateador (on-deck / prevenido).
     * Es el siguiente atleta en el lineup del equipo al bate que NO ha bateado
     * en este inning.
     */
    private function onDeck(Game $game, array $state): ?array
    {
        $battingTeamId = $state['half'] === 'top' ? $game->away_team_id : $game->home_team_id;

        // Atletas del lineup del equipo al bate, ordenados por lineup_order
        $lineup = DB::table('game_athlete')
            ->where('game_id', $game->id)
            ->where('team_id', $battingTeamId)
            ->orderBy('lineup_order')
            ->pluck('athlete_id')
            ->toArray();

        if (empty($lineup)) {
            return null;
        }

        // Atleta que ya bateo en este inning
        $battersThisInning = DB::table('plays')
            ->where('game_id', $game->id)
            ->where('inning', $state['inning'])
            ->where('half', $state['half'])
            ->whereIn('type', ['hit', 'out', 'walk', 'hbp', 'error', 'bunt'])
            ->orderBy('sequence')
            ->pluck('batter_id')
            ->filter()
            ->unique()
            ->toArray();

        $currentBatter = $state['current_batter_id'];

        // Encuentra el siguiente en el orden
        $next = null;
        foreach ($lineup as $i => $aid) {
            // Si el current es el siguiente, entonces on-deck es el subsiguiente
            if ($currentBatter && $aid === $currentBatter) {
                $next = $lineup[$i + 1] ?? $lineup[0];
                break;
            }
            // Si este atleta NO ha bateado, es el on-deck
            if (! in_array($aid, $battersThisInning, true) && $aid !== $currentBatter) {
                $next = $aid;
                break;
            }
        }

        if (! $next) {
            $next = $lineup[0];
        }

        $a = \App\Models\Athlete::find($next);

        return $a ? [
            'id' => $a->id,
            'name' => $a->full_name,
            'number' => $a->number,
        ] : null;
    }
}
