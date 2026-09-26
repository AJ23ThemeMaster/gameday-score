<?php
/**
 * Resetea el game 1: ELIMINA todos los plays y crea uno nuevo en inning=1 top.
 * Usa batter_id y pitcher_id reales del roster para que el engine funcione.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Game;
use App\Models\Play;
use App\Services\GameplayEngine;
use Illuminate\Support\Facades\DB;

// Eliminar TODOS los plays del game 1
DB::table('plays')->where('game_id', 1)->delete();

// Obtener roster: el away batea en top, el home pichea
$game = Game::find(1);
$awayRoster = DB::table('game_athlete')->where('game_id', 1)->where('team_id', $game->away_team_id)->orderBy('athlete_id')->get();
$homeRoster = DB::table('game_athlete')->where('game_id', 1)->where('team_id', $game->home_team_id)->orderBy('athlete_id')->get();

$firstBatter = $awayRoster->first()?->athlete_id;
$homePitcher = $homeRoster->first()?->athlete_id;

echo "Roster away: " . $awayRoster->count() . " atletas (primer batter: {$firstBatter})\n";
echo "Roster home: " . $homeRoster->count() . " atletas (primer pitcher: {$homePitcher})\n";

// Crear UN solo play en inning=1 half=top con batter/pitcher reales
$play = new Play();
$play->game_id = 1;
$play->inning = 1;
$play->half = 'top';
$play->sequence = 1;
$play->type = 'pitch';
$play->subtype = 'at_bat_start';
$play->result = 'Juego reiniciado para E2E - primer bateador';
$play->batter_id = $firstBatter;
$play->pitcher_id = $homePitcher;
$play->outs_before = 0;
$play->outs_after = 0;
$play->balls = 0;
$play->strikes = 0;
$play->bases_before = ['first' => null, 'second' => null, 'third' => null];
$play->bases_after = ['first' => null, 'second' => null, 'third' => null];
$play->runs_scored = 0;
$play->rbi = 0;
$play->meta = [];
$play->recorded_by = null;
$play->save();

echo "Reset completo: id={$play->id} inning=1 half=top batter={$firstBatter} pitcher={$homePitcher}\n";

// Resetear Game
$game->current_inning = 1;
$game->inning_half = 'top';
$game->status = 'in_progress';
$game->save();
