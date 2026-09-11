<?php

declare(strict_types=1);

use App\Http\Controllers\AthleteController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicGameController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\ScoreboardController;
use App\Http\Controllers\ScorekeeperController;
use App\Http\Controllers\StadiumController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Vista pública del juego (sin auth) — DISI-8
Route::get('/juego/publico/{token}', [PublicGameController::class, 'show'])
    ->name('public.games.show');

// DISI-9: Endpoint JSON para polling de la vista pública (sin auth)
Route::get('/juego/publico/{token}/state', [PublicGameController::class, 'stateJson'])
    ->name('public.games.state');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Perfil del usuario (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRUDs (DISI-4, DISI-5, DISI-6, DISI-7)
    Route::resource('categories', CategoryController::class);
    Route::resource('stadiums', StadiumController::class);
    Route::resource('teams', TeamController::class);
    Route::resource('athletes', AthleteController::class);
    Route::resource('scorekeepers', ScorekeeperController::class);
    Route::resource('referees', RefereeController::class);
    // DISI-13: CRUDs de Ligas y Torneos
    Route::resource('leagues', LeagueController::class);
    Route::resource('tournaments', TournamentController::class);
    Route::resource('games', GameController::class);

    // DISI-9: Scoreboard en vivo (control del juego por el owner)
    Route::get('games/{game}/live', [GameController::class, 'live'])->name('games.live');
    Route::patch('games/{game}/state', [GameController::class, 'updateState'])->name('games.state.update');
    Route::post('games/{game}/runs', [GameController::class, 'addRun'])->name('games.runs.add');
    Route::post('games/{game}/end-inning', [GameController::class, 'endInning'])->name('games.end-inning');

    // DISI-12: Nuevo scoreboard moderno (Fase 1: layout + live AJAX sin recargar)
    Route::get('games/{game}/scoreboard', [ScoreboardController::class, 'show'])->name('games.scoreboard');
    Route::get('games/{game}/scoreboard/poll', [ScoreboardController::class, 'poll'])->name('games.scoreboard.poll');
    // DISI-12 MEJ-3: stats historicas del juego (box score)
    Route::get('games/{game}/scoreboard/stats', [ScoreboardController::class, 'stats'])->name('games.scoreboard.stats');
    // DISI-12 MEJ-4: reordenar lineup (drag&drop)
    Route::patch('games/{game}/lineup/order', [ScoreboardController::class, 'reorderLineup'])->name('games.lineup.reorder');
    // DISI-12 (fases siguientes): registrar jugadas
    Route::post('games/{game}/plays', [\App\Http\Controllers\PlayController::class, 'store'])->name('games.plays.store');
    Route::get('games/{game}/plays', [\App\Http\Controllers\PlayController::class, 'index'])->name('games.plays.index');
    // DISI-12 Fase 2: endpoint del engine de pitcheo (ball, strike, foul, out)
    Route::post('games/{game}/pitch', [\App\Http\Controllers\PlayController::class, 'pitch'])->name('games.plays.pitch');
    // DISI-12 Fase 4: finalizar inning/juego manualmente
    Route::post('games/{game}/end-inning', [\App\Http\Controllers\PlayController::class, 'endInning'])->name('games.plays.end-inning');
    Route::post('games/{game}/end-game', [\App\Http\Controllers\PlayController::class, 'endGame'])->name('games.plays.end-game');
    // DISI-12 Fase 4b: sustituciones (pitcher, bateador, pinch runner)
    Route::post('games/{game}/substitute', [\App\Http\Controllers\PlayController::class, 'substitute'])->name('games.plays.substitute');

    // DISI-10: Roster y sustituciones
    Route::get('games/{game}/roster', [RosterController::class, 'index'])->name('games.roster.index');
    Route::post('games/{game}/roster/athletes', [RosterController::class, 'store'])->name('games.roster.store');
    Route::patch('games/{game}/roster/{athlete}', [RosterController::class, 'update'])->name('games.roster.update');
    Route::delete('games/{game}/roster/{athlete}', [RosterController::class, 'destroy'])->name('games.roster.destroy');
    Route::post('games/{game}/roster/substitute', [RosterController::class, 'substitute'])->name('games.roster.substitute');
});

require __DIR__.'/auth.php';
