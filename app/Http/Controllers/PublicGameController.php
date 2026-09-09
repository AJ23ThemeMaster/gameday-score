<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
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
}
