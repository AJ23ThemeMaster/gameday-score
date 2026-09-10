<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddAthleteToRosterRequest;
use App\Http\Requests\SubstituteAthleteRequest;
use App\Http\Requests\UpdateRosterEntryRequest;
use App\Models\Athlete;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RosterController extends Controller
{
    public function index(Game $game): View
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $game->load([
            'category', 'stadium', 'homeTeam', 'awayTeam',
            'homeTeam.athletes', 'awayTeam.athletes',
        ]);

        // Atletas actualmente en el roster (game_athlete)
        $rosterEntries = $game->athletes()
            ->withPivot([
                'team_id', 'lineup_order', 'position',
                'is_starter', 'is_pitcher', 'pitches_thrown',
                'at_bats', 'hits', 'runs', 'rbi',
            ])
            ->get()
            ->keyBy('id');

        // Atletas disponibles por equipo (los del team que NO están en el roster)
        $homeAvailable = $game->homeTeam->athletes->whereNotIn('id', $rosterEntries->keys());
        $awayAvailable = $game->awayTeam->athletes->whereNotIn('id', $rosterEntries->keys());

        // Lanzadores actuales
        $homePitcher = $rosterEntries->first(fn ($a) => $a->pivot->is_pitcher && $a->pivot->team_id === $game->home_team_id);
        $awayPitcher = $rosterEntries->first(fn ($a) => $a->pivot->is_pitcher && $a->pivot->team_id === $game->away_team_id);

        return view('games.roster.index', compact(
            'game', 'rosterEntries', 'homeAvailable', 'awayAvailable', 'homePitcher', 'awayPitcher'
        ));
    }

    public function store(AddAthleteToRosterRequest $request, Game $game): RedirectResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $data = $request->validated();
        $data['is_starter'] = $request->boolean('is_starter', true);

        // Si este atleta es pitcher, quitar el flag de cualquier otro pitcher del mismo equipo
        if ($request->boolean('is_pitcher')) {
            $game->athletes()
                ->wherePivot('team_id', $data['team_id'])
                ->wherePivot('is_pitcher', true)
                ->updateExistingPivot(
                    $game->athletes()->wherePivot('team_id', $data['team_id'])->pluck('athletes.id')->toArray(),
                    ['is_pitcher' => false]
                );
        }

        // syncWithoutDetaching para que si ya existe (edge case), solo actualice
        $game->athletes()->syncWithoutDetaching([
            $data['athlete_id'] => [
                'team_id' => $data['team_id'],
                'lineup_order' => $data['lineup_order'] ?? null,
                'position' => $data['position'] ?? null,
                'is_starter' => $data['is_starter'],
                'is_pitcher' => $request->boolean('is_pitcher'),
            ],
        ]);

        $athlete = Athlete::find($data['athlete_id']);

        return redirect()
            ->route('games.roster.index', $game)
            ->with('status', "«{$athlete->full_name}» agregado al roster.");
    }

    public function update(UpdateRosterEntryRequest $request, Game $game, Athlete $athlete): RedirectResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $pivot = $game->athletes()->where('athlete_id', $athlete->id)->first()?->pivot;
        if (! $pivot) {
            return redirect()
                ->route('games.roster.index', $game)
                ->with('error', "«{$athlete->full_name}» no está en el roster.");
        }

        $data = array_filter($request->validated(), fn ($v) => $v !== null);

        // Si lo marca como pitcher, desmarcar el pitcher actual del mismo equipo
        if ($request->boolean('is_pitcher') || (isset($data['is_pitcher']) && $data['is_pitcher'])) {
            $otherPitchers = $game->athletes()
                ->wherePivot('team_id', $pivot->team_id)
                ->wherePivot('is_pitcher', true)
                ->where('athletes.id', '!=', $athlete->id)
                ->pluck('athletes.id')
                ->toArray();
            if ($otherPitchers) {
                $game->athletes()->updateExistingPivot($otherPitchers, ['is_pitcher' => false]);
            }
        }

        $game->athletes()->updateExistingPivot($athlete->id, $data);

        return redirect()
            ->route('games.roster.index', $game)
            ->with('status', "«{$athlete->full_name}» actualizado.");
    }

    public function destroy(Game $game, Athlete $athlete): RedirectResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $name = $athlete->full_name;
        $game->athletes()->detach($athlete->id);

        return redirect()
            ->route('games.roster.index', $game)
            ->with('status', "«{$name}» removido del roster.");
    }

    public function substitute(SubstituteAthleteRequest $request, Game $game): RedirectResponse
    {
        abort_unless($game->user_id === Auth::id(), 403);

        $data = $request->validated();

        // Transacción atómica: detach del viejo, attach del nuevo con mismas props
        DB::transaction(function () use ($game, $data, $request) {
            $outPivot = $game->athletes()
                ->where('athlete_id', $data['out_athlete_id'])
                ->first()?->pivot;

            if (! $outPivot) {
                abort(422, 'El atleta que sale no está en el roster.');
            }

            // Capturar las stats del atleta que sale (acumuladas hasta la sustitución)
            $stats = [
                'pitches_thrown' => $outPivot->pitches_thrown,
                'at_bats' => $outPivot->at_bats,
                'hits' => $outPivot->hits,
                'runs' => $outPivot->runs,
                'rbi' => $outPivot->rbi,
            ];

            // Remover al viejo
            $game->athletes()->detach($data['out_athlete_id']);

            // Adjuntar al nuevo con la misma posición / lineup / team
            $attachData = array_merge([
                'team_id' => $outPivot->team_id,
                'lineup_order' => $data['lineup_order'] ?? $outPivot->lineup_order,
                'position' => $data['position'] ?? $outPivot->position,
                'is_starter' => false, // entra como sustituto
                'is_pitcher' => $request->boolean('is_pitcher', (bool) $outPivot->is_pitcher),
            ], $stats);

            $game->athletes()->attach($data['in_athlete_id'], $attachData);

            // Si el nuevo es pitcher, desmarcar al pitcher actual del equipo
            if ($attachData['is_pitcher']) {
                $otherPitchers = $game->athletes()
                    ->wherePivot('team_id', $outPivot->team_id)
                    ->wherePivot('is_pitcher', true)
                    ->where('athletes.id', '!=', $data['in_athlete_id'])
                    ->pluck('athletes.id')
                    ->toArray();
                if ($otherPitchers) {
                    $game->athletes()->updateExistingPivot($otherPitchers, ['is_pitcher' => false]);
                }
            }
        });

        $inName = Athlete::find($data['in_athlete_id'])->full_name;
        $outName = Athlete::find($data['out_athlete_id'])->full_name;

        return redirect()
            ->route('games.roster.index', $game)
            ->with('status', "Sustitución: «{$inName}» entró por «{$outName}».");
    }
}
