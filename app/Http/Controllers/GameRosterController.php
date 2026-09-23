<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithRosterPartial;
use App\Http\Requests\AddAthleteToRosterRequest;
use App\Http\Requests\SubstituteAthleteRequest;
use App\Http\Requests\UpdateRosterEntryRequest;
use App\Models\Athlete;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Roster POR-JUEGO: gestiona la nomina de atletas que participaran en un
 * juego especifico (lineup, titulares, pitchers, sustituciones). Es
 * distinto del roster POR-EQUIPO/CATEGORIA (App\Models\Roster) que es
 * la plantilla del equipo para una temporada. Ambos coexisten: este
 * controller solo opera sobre la tabla pivote game_athlete.
 */
class GameRosterController extends Controller
{
    use RespondsWithRosterPartial;

    public function index(Game $game): View
    {
        abort_unless(
            Auth::check() && ($game->user_id === Auth::id() || Auth::user()->hasRole('admin')),
            403,
            'No tienes permiso para gestionar el roster de este juego. Solo el administrador o el anotador del juego pueden hacerlo.'
        );

        $game->load([
            'category', 'stadium', 'homeTeam', 'awayTeam',
            'homeTeam.athletes', 'awayTeam.athletes',
        ]);

        $rosterEntries = $game->athletes()
            ->withPivot([
                'team_id', 'lineup_order', 'position',
                'is_starter', 'is_pitcher', 'pitches_thrown',
                'at_bats', 'hits', 'runs', 'rbi',
            ])
            ->get()
            ->keyBy('id');

        $homeAvailable = $game->homeTeam->athletes->whereNotIn('id', $rosterEntries->keys());
        $awayAvailable = $game->awayTeam->athletes->whereNotIn('id', $rosterEntries->keys());

        $homePitcher = $rosterEntries->first(fn ($a) => $a->pivot->is_pitcher && $a->pivot->team_id === $game->home_team_id);
        $awayPitcher = $rosterEntries->first(fn ($a) => $a->pivot->is_pitcher && $a->pivot->team_id === $game->away_team_id);

        return view('games.roster.index', compact(
            'game', 'rosterEntries', 'homeAvailable', 'awayAvailable', 'homePitcher', 'awayPitcher'
        ));
    }

    public function store(AddAthleteToRosterRequest $request, Game $game): RedirectResponse|JsonResponse
    {
        abort_unless(
            Auth::check() && ($game->user_id === Auth::id() || Auth::user()->hasRole('admin')),
            403,
            'No tienes permiso para gestionar el roster de este juego. Solo el administrador o el anotador del juego pueden hacerlo.'
        );

        $data = $request->validated();
        $data['is_starter'] = $request->boolean('is_starter', true);

        // Si este atleta es pitcher, quitar el flag de cualquier otro pitcher del mismo equipo
        if ($request->boolean('is_pitcher')) {
            $otherPitchers = $game->athletes()
                ->wherePivot('team_id', $data['team_id'])
                ->wherePivot('is_pitcher', true)
                ->pluck('athletes.id')
                ->toArray();
            if ($otherPitchers) {
                $game->athletes()->updateExistingPivot($otherPitchers, ['is_pitcher' => false]);
            }
        }

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

        return $this->rosterResponse(
            $request,
            $game,
            "«{$athlete->full_name}» agregado al roster.",
        );
    }

    public function update(UpdateRosterEntryRequest $request, Game $game, Athlete $athlete): RedirectResponse|JsonResponse
    {
        abort_unless(
            Auth::check() && ($game->user_id === Auth::id() || Auth::user()->hasRole('admin')),
            403,
            'No tienes permiso para gestionar el roster de este juego. Solo el administrador o el anotador del juego pueden hacerlo.'
        );

        $pivot = $game->athletes()->where('athlete_id', $athlete->id)->first()?->pivot;
        if (! $pivot) {
            return $this->rosterResponse(
                $request, $game,
                "«{$athlete->full_name}» no está en el roster.",
                'error',
            );
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

        return $this->rosterResponse(
            $request,
            $game,
            "«{$athlete->full_name}» actualizado.",
        );
    }

    public function destroy(\Illuminate\Http\Request $request, Game $game, Athlete $athlete): RedirectResponse|JsonResponse
    {
        abort_unless(
            Auth::check() && ($game->user_id === Auth::id() || Auth::user()->hasRole('admin')),
            403,
            'No tienes permiso para gestionar el roster de este juego. Solo el administrador o el anotador del juego pueden hacerlo.'
        );

        $name = $athlete->full_name;
        $game->athletes()->detach($athlete->id);

        return $this->rosterResponse(
            $request,
            $game,
            "«{$name}» removido del roster.",
        );
    }

    public function substitute(SubstituteAthleteRequest $request, Game $game): RedirectResponse|JsonResponse
    {
        abort_unless(
            Auth::check() && ($game->user_id === Auth::id() || Auth::user()->hasRole('admin')),
            403,
            'No tienes permiso para gestionar el roster de este juego. Solo el administrador o el anotador del juego pueden hacerlo.'
        );

        $data = $request->validated();

        DB::transaction(function () use ($game, $data, $request) {
            $outPivot = $game->athletes()
                ->where('athlete_id', $data['out_athlete_id'])
                ->first()?->pivot;

            if (! $outPivot) {
                abort(422, 'El atleta que sale no está en el roster.');
            }

            $stats = [
                'pitches_thrown' => $outPivot->pitches_thrown,
                'at_bats' => $outPivot->at_bats,
                'hits' => $outPivot->hits,
                'runs' => $outPivot->runs,
                'rbi' => $outPivot->rbi,
            ];

            $game->athletes()->detach($data['out_athlete_id']);

            $attachData = array_merge([
                'team_id' => $outPivot->team_id,
                'lineup_order' => $data['lineup_order'] ?? $outPivot->lineup_order,
                'position' => $data['position'] ?? $outPivot->position,
                'is_starter' => false,
                'is_pitcher' => $request->boolean('is_pitcher', (bool) $outPivot->is_pitcher),
            ], $stats);

            $game->athletes()->attach($data['in_athlete_id'], $attachData);

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

        return $this->rosterResponse(
            $request,
            $game,
            "Sustitución: «{$inName}» entró por «{$outName}».",
        );
    }
}
