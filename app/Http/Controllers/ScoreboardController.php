<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Athlete;
use App\Models\Game;
use App\Models\Play;
use App\Services\GameplayEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScoreboardController extends Controller
{
    public function __construct(private readonly GameplayEngine $engine)
    {
    }

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

        [$state, $pitcher, $batter, $onDeck, $pitcherStats, $batterStats] =
            $this->buildSnapshot($game);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'state' => $state,
                'score' => Play::scoreboard($game->id),
                'pitcher' => $pitcher ? $this->athleteToArray($pitcher) : null,
                'pitcher_stats' => $pitcherStats,
                'batter' => $batter ? $this->athleteToArray($batter) : null,
                'batter_stats' => $batterStats,
                'on_deck' => $onDeck ? $this->athleteToArray($onDeck) : null,
                'runners' => $this->runners($state),
            ]);
        }

        $score = Play::scoreboard($game->id);
        $runners = $this->runners($state);

        return view('games.scoreboard', compact(
            'game', 'state', 'score', 'pitcher', 'batter', 'onDeck', 'runners',
            'pitcherStats', 'batterStats',
        ));
    }

    /**
     * Live poll endpoint: solo el state JSON, no la vista completa.
     * El cliente lo llama cada 5s para mantener el scoreboard sincronizado.
     */
    public function poll(Request $request, Game $game): JsonResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        [$state, $pitcher, $batter, $onDeck, $pitcherStats, $batterStats] =
            $this->buildSnapshot($game);

        return response()->json([
            'state' => $state,
            'score' => Play::scoreboard($game->id),
            'pitcher' => $pitcher ? $this->athleteToArray($pitcher) : null,
            'pitcher_stats' => $pitcherStats,
            'batter' => $batter ? $this->athleteToArray($batter) : null,
            'batter_stats' => $batterStats,
            'on_deck' => $onDeck ? $this->athleteToArray($onDeck) : null,
            'runners' => $this->runners($state),
        ]);
    }

    /**
     * Construye el snapshot completo: state + pitcher/batter + on-deck + stats.
     *
     * @return array{0: array, 1: ?Athlete, 2: ?Athlete, 3: ?Athlete, 4: array, 5: array}
     */
    private function buildSnapshot(Game $game): array
    {
        $state = Play::currentState($game->id);

        // Si la ultima jugada cerro el medio inning (inning_end) o el juego
        // (game_end), hay que recalcular pitcher/bateador para el nuevo half,
        // porque la jugada guardada pertenece al half anterior.
        $recalculate = in_array($state['last_play_type'] ?? null, [
            Play::TYPE_INNING_END,
            Play::TYPE_GAME_END,
        ], true) || $state['outs'] === 0;

        if (! $state['current_batter_id'] || $recalculate) {
            $state['current_batter_id'] = $this->engine->firstBatter($game, $state['half']);
        }
        if ($recalculate) {
            $defendingTeamId = $state['half'] === 'top' ? $game->home_team_id : $game->away_team_id;
            $state['current_pitcher_id'] = $this->engine->pitcherFor($game, $defendingTeamId);
        }

        $pitcher = $state['current_pitcher_id'] ? Athlete::find($state['current_pitcher_id']) : null;
        $batter = $state['current_batter_id'] ? Athlete::find($state['current_batter_id']) : null;

        // On-deck: siguiente bateador del lineup (el engine respeta el wrap 9->1).
        $onDeckId = $this->engine->nextBatter($game, $state['half'], $state['current_batter_id']);
        $onDeck = $onDeckId ? Athlete::find($onDeckId) : null;

        $pitcherStats = $pitcher ? Play::statsForPitcher($game->id, $pitcher->id) : $this->emptyPitcherStats();
        $batterStats = $batter ? Play::statsForBatter($game->id, $batter->id) : $this->emptyBatterStats();

        return [$state, $pitcher, $batter, $onDeck, $pitcherStats, $batterStats];
    }

    private function athleteToArray(Athlete $a): array
    {
        $firstInitial = mb_strtoupper(mb_substr($a->first_name ?? '', 0, 1));
        $lastInitial = mb_strtoupper(mb_substr($a->last_name ?? '', 0, 1));

        return [
            'id' => $a->id,
            'name' => $a->full_name,
            'number' => $a->number,
            'team_id' => $a->team?->id,
            'photo_url' => $a->photoUrl,
            'initials' => $firstInitial . $lastInitial,
        ];
    }

    private function emptyPitcherStats(): array
    {
        return ['pitches' => 0, 'strikes' => 0, 'balls' => 0, 'strikeouts' => 0, 'hits' => 0, 'walks' => 0];
    }

    private function emptyBatterStats(): array
    {
        return ['at_bats' => 0, 'hits' => 0, 'strikeouts' => 0, 'walks' => 0, 'avg' => 0.0];
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
            $a = Athlete::find($athleteId);
            if ($a) {
                $out[$base] = $this->athleteToArray($a);
            }
        }

        return $out;
    }
}
