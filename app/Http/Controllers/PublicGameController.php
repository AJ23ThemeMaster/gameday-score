<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Play;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicGameController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $game = Game::with([
            'category', 'stadium', 'homeTeam', 'awayTeam',
            'scorekeepers', 'referees',
        ])
            ->where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        // DISI-48: feed jugada por jugada agrupado por inning (top/bottom).
        // Cada pitch se incluye (bola, strike, foul) ademas de outs, hits,
        // walks, bunts, balks, robos y cierres de inning. La vista Blade
        // se encarga del render. El grouping se hace aqui para que la vista
        // no tenga logica de negocio.
        $plays = Play::where('game_id', $game->id)
            ->with(['batter', 'pitcher'])
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        $playByPlay = $this->groupPlaysByInning($plays);

        return view('public.games.show', compact('game', 'playByPlay'));
    }

    /**
     * Agrupa las jugadas por inning y half en una estructura lista para iterar en Blade:
     * [
     *   ['inning' => 1, 'top' => [Play, ...], 'bottom' => [Play, ...]],
     *   ['inning' => 2, 'top' => [...], 'bottom' => [...]],
     *   ...
     * ]
     *
     * Filtra jugadas tipo `inning_end` y `game_end` (son marcadores internos
     * que el motor graba con el inning/half NUEVO, no el que acaba de cerrar,
     * asi que aparecerian en el bucket incorrecto). Los headers de inning/half
     * ya dan la estructura visual sin necesidad de estos marcadores.
     *
     * Solo se incluyen innings que tienen al menos una jugada en top o bottom.
     */
    private function groupPlaysByInning(\Illuminate\Database\Eloquent\Collection $plays): array
    {
        $grouped = [];
        foreach ($plays as $p) {
            // Saltar marcadores de cierre: el cambio de inning/half ya es
            // visible en los headers y en la transicion top->bottom.
            if (in_array($p->type, [Play::TYPE_INNING_END, Play::TYPE_GAME_END], true)) {
                continue;
            }
            $inning = (int) $p->inning;
            $half = $p->half === 'top' ? 'top' : 'bottom';
            $grouped[$inning][$half][] = $p;
        }

        ksort($grouped);
        $result = [];
        foreach ($grouped as $inning => $halves) {
            $result[] = [
                'inning' => $inning,
                'top' => $halves['top'] ?? [],
                'bottom' => $halves['bottom'] ?? [],
            ];
        }

        return $result;
    }

    public function stateJson(Request $request, string $token): JsonResponse
    {
        $game = Game::with(['homeTeam', 'awayTeam'])
            ->where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        return response()->json([
            'status' => $game->status,
            'current_inning' => $game->current_inning,
            'inning_half' => $game->inning_half,
            'balls' => $game->balls,
            'strikes' => $game->strikes,
            'outs' => $game->outs,
            'bases' => $game->bases,
            'home_score' => $game->home_score,
            'away_score' => $game->away_score,
            'home_team' => ['id' => $game->homeTeam->id, 'name' => $game->homeTeam->name, 'logo_url' => $game->homeTeam->logoUrl],
            'away_team' => ['id' => $game->awayTeam->id, 'name' => $game->awayTeam->name, 'logo_url' => $game->awayTeam->logoUrl],
            'updated_at' => $game->updated_at->toIso8601String(),
        ]);
    }
}
