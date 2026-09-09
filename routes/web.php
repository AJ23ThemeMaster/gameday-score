<?php

declare(strict_types=1);

use App\Http\Controllers\AthleteController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\ScorekeeperController;
use App\Http\Controllers\StadiumController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Perfil del usuario (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRUDs (DISI-4, DISI-5, DISI-6)
    Route::resource('categories', CategoryController::class);
    Route::resource('stadiums', StadiumController::class);
    Route::resource('teams', TeamController::class);
    Route::resource('athletes', AthleteController::class);
    Route::resource('scorekeepers', ScorekeeperController::class);
    Route::resource('referees', RefereeController::class);
});

require __DIR__.'/auth.php';
