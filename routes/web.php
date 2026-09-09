<?php

declare(strict_types=1);

use App\Http\Controllers\AthleteController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicGameController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\ScorekeeperController;
use App\Http\Controllers\StadiumController;
use App\Http\Controllers\TeamController;
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
    Route::resource('games', GameController::class);

    // DISI-9: Scoreboard en vivo (control del juego por el owner)
    Route::get('games/{game}/live', [GameController::class, 'live'])->name('games.live');
    Route::patch('games/{game}/state', [GameController::class, 'updateState'])->name('games.state.update');
    Route::post('games/{game}/runs', [GameController::class, 'addRun'])->name('games.runs.add');
    Route::post('games/{game}/end-inning', [GameController::class, 'endInning'])->name('games.end-inning');
});

require __DIR__.'/auth.php';
