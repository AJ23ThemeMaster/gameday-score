<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Play;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlayController extends Controller
{
    /**
     * Feed de jugadas (play-by-play) del juego.
     * Devuelve el HTML del listado para refresco AJAX.
     */
    public function index(Request $request, Game $game): View|JsonResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $plays = Play::where('game_id', $game->id)
            ->with(['batter', 'pitcher'])
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'plays' => $plays->map(fn ($p) => [
                    'id' => $p->id,
                    'inning' => $p->inning,
                    'half' => $p->half,
                    'sequence' => $p->sequence,
                    'type' => $p->type,
                    'subtype' => $p->subtype,
                    'result' => $p->result,
                    'batter' => $p->batter?->full_name,
                    'pitcher' => $p->pitcher?->full_name,
                    'runs_scored' => $p->runs_scored,
                    'recorded_at' => $p->recorded_at->toIso8601String(),
                ])->values(),
            ]);
        }

        return view('games.plays.index', compact('game', 'plays'));
    }

    /**
     * Registrar una jugada. Usado por las fases siguientes (Pitcheo, Bateo, Extras).
     * En Fase 1 solo el endpoint existe; la UI para llamar a este endpoint
     * se construye en fases posteriores.
     */
    public function store(Request $request, Game $game): JsonResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'inning' => ['required', 'integer', 'min:1'],
            'half' => ['required', 'in:top,bottom'],
            'type' => ['required', 'string', 'in:' . implode(',', [
                Play::TYPE_PITCH, Play::TYPE_OUT, Play::TYPE_HIT,
                Play::TYPE_WALK, Play::TYPE_HBP, Play::TYPE_ERROR,
                Play::TYPE_BUNT, Play::TYPE_BALK, Play::TYPE_RUNNER_MOVEMENT,
                Play::TYPE_SUBSTITUTION, Play::TYPE_INNING_END, Play::TYPE_GAME_END,
            ])],
            'subtype' => ['nullable', 'string', 'max:30'],
            'result' => ['nullable', 'string', 'max:60'],
            'batter_id' => ['nullable', 'exists:athletes,id'],
            'pitcher_id' => ['nullable', 'exists:athletes,id'],
            'outs_before' => ['nullable', 'integer', 'min:0', 'max:2'],
            'outs_after' => ['nullable', 'integer', 'min:0', 'max:3'],
            'balls' => ['nullable', 'integer', 'min:0', 'max:4'],
            'strikes' => ['nullable', 'integer', 'min:0', 'max:3'],
            'runs_scored' => ['nullable', 'integer', 'min:0'],
            'rbi' => ['nullable', 'integer', 'min:0'],
            'bases_before' => ['nullable', 'array'],
            'bases_after' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        // Siguiente sequence
        $sequence = Play::where('game_id', $game->id)
            ->where('inning', $validated['inning'])
            ->where('half', $validated['half'])
            ->max('sequence') + 1;

        $play = Play::create(array_merge($validated, [
            'game_id' => $game->id,
            'sequence' => $sequence,
            'recorded_by' => Auth::id(),
            'recorded_at' => now(),
            'outs_before' => $validated['outs_before'] ?? 0,
            'outs_after' => $validated['outs_after'] ?? 0,
            'balls' => $validated['balls'] ?? 0,
            'strikes' => $validated['strikes'] ?? 0,
            'runs_scored' => $validated['runs_scored'] ?? 0,
            'rbi' => $validated['rbi'] ?? 0,
        ]));

        return response()->json([
            'success' => true,
            'play' => $play,
            'message' => 'Jugada registrada.',
        ], 201);
    }
}
