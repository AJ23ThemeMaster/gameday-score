<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeagueRequest;
use App\Http\Requests\UpdateLeagueRequest;
use App\Models\League;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LeagueController extends Controller
{
    public function index(): View
    {
        $leagues = League::orderBy('name')
            ->withCount(['tournaments', 'games'])
            ->paginate(15);

        return view('leagues.index', compact('leagues'));
    }

    public function create(): View
    {
        return view('leagues.create', ['league' => new League()]);
    }

    public function store(StoreLeagueRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')
                ->store('leagues', 'public');
        }

        $league = League::create($data);

        return redirect()
            ->route('leagues.show', $league)
            ->with('status', "Liga «{$league->name}» creada correctamente.");
    }

    public function show(League $league): View
    {
        $league->loadCount(['tournaments', 'games']);
        $tournaments = $league->tournaments()
            ->orderBy('name')
            ->withCount('games')
            ->get();

        return view('leagues.show', compact('league', 'tournaments'));
    }

    public function edit(League $league): View
    {
        return view('leagues.edit', compact('league'));
    }

    public function update(UpdateLeagueRequest $request, League $league): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $league->active);

        if ($request->boolean('remove_logo') && $league->logo_path) {
            Storage::disk('public')->delete($league->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($league->logo_path) {
                Storage::disk('public')->delete($league->logo_path);
            }
            $data['logo_path'] = $request->file('logo')
                ->store('leagues', 'public');
        }

        $league->update($data);

        return redirect()
            ->route('leagues.show', $league)
            ->with('status', "Liga «{$league->name}» actualizada correctamente.");
    }

    public function destroy(League $league): RedirectResponse
    {
        if ($league->games()->exists() || $league->tournaments()->exists()) {
            return redirect()
                ->route('leagues.index')
                ->with('error', "No se puede eliminar la liga «{$league->name}» porque tiene torneos o juegos asociados.");
        }

        if ($league->logo_path) {
            Storage::disk('public')->delete($league->logo_path);
        }

        $name = $league->name;
        $league->delete();

        return redirect()
            ->route('leagues.index')
            ->with('status', "Liga «{$name}» eliminada correctamente.");
    }
}
