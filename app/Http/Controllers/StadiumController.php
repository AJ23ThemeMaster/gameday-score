<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreStadiumRequest;
use App\Http\Requests\UpdateStadiumRequest;
use App\Models\Stadium;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StadiumController extends Controller
{
    public function index(): View
    {
        $stadiums = Stadium::orderBy('name')
            ->withCount('games')
            ->paginate(15);

        return view('stadiums.index', compact('stadiums'));
    }

    public function create(): View
    {
        return view('stadiums.create');
    }

    public function store(StoreStadiumRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        $stadium = Stadium::create($data);

        return redirect()
            ->route('stadiums.show', $stadium)
            ->with('status', "Estadio «{$stadium->name}» creado correctamente.");
    }

    public function show(Stadium $stadium): View
    {
        $stadium->loadCount('games');

        return view('stadiums.show', compact('stadium'));
    }

    public function edit(Stadium $stadium): View
    {
        return view('stadiums.edit', compact('stadium'));
    }

    public function update(UpdateStadiumRequest $request, Stadium $stadium): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $stadium->active);
        $stadium->update($data);

        return redirect()
            ->route('stadiums.show', $stadium)
            ->with('status', "Estadio «{$stadium->name}» actualizado correctamente.");
    }

    public function destroy(Stadium $stadium): RedirectResponse
    {
        if ($stadium->games()->exists()) {
            return redirect()
                ->route('stadiums.index')
                ->with('error', "No se puede eliminar el estadio «{$stadium->name}» porque tiene juegos asociados.");
        }

        $name = $stadium->name;
        $stadium->delete();

        return redirect()
            ->route('stadiums.index')
            ->with('status', "Estadio «{$name}» eliminado correctamente.");
    }
}
