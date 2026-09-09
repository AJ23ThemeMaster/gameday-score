<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
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

        return view('public.games.show', compact('game'));
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
