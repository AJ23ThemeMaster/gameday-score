<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAthleteRequest;
use App\Http\Requests\UpdateAthleteRequest;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AthleteController extends Controller
{
    public function index(): View
    {
        $athletes = Athlete::with(['team', 'category'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('athletes.index', compact('athletes'));
    }

    public function create(): View
    {
        $teams = Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('athletes.create', compact('teams', 'categories'));
    }

    public function store(StoreAthleteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo para PHP 8.4 strict types
        $data['team_id'] = $data['team_id'] !== null ? (int) $data['team_id'] : null;
        $data['category_id'] = $data['category_id'] !== null ? (int) $data['category_id'] : null;

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
        }

        $athlete = Athlete::create($data);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', "Atleta «{$athlete->full_name}» creado correctamente.");
    }

    public function show(Athlete $athlete): View
    {
        $athlete->load(['team', 'category']);

        return view('athletes.show', compact('athlete'));
    }

    public function edit(Athlete $athlete): View
    {
        $teams = Team::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('athletes.edit', compact('athlete', 'teams', 'categories'));
    }

    public function update(UpdateAthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $athlete->active);
        $data['team_id'] = $data['team_id'] !== null ? (int) $data['team_id'] : null;
        $data['category_id'] = $data['category_id'] !== null ? (int) $data['category_id'] : null;

        if ($request->hasFile('photo')) {
            $this->deletePhoto($athlete);
            $data['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($athlete);
            $data['photo_path'] = null;
        }

        $athlete->update($data);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', "Atleta «{$athlete->full_name}» actualizado correctamente.");
    }

    public function destroy(Athlete $athlete): RedirectResponse
    {
        $name = $athlete->full_name;
        $this->deletePhoto($athlete);
        $athlete->delete();

        return redirect()
            ->route('athletes.index')
            ->with('status', "Atleta «{$name}» eliminado correctamente.");
    }

    private function deletePhoto(Athlete $athlete): void
    {
        if ($athlete->photo_path && Storage::disk('public')->exists($athlete->photo_path)) {
            Storage::disk('public')->delete($athlete->photo_path);
        }
    }
}
