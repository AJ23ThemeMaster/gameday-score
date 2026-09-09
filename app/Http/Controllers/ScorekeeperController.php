<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreScorekeeperRequest;
use App\Http\Requests\UpdateScorekeeperRequest;
use App\Models\Scorekeeper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ScorekeeperController extends Controller
{
    public function index(): View
    {
        $scorekeepers = Scorekeeper::orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('scorekeepers.index', compact('scorekeepers'));
    }

    public function create(): View
    {
        return view('scorekeepers.create');
    }

    public function store(StoreScorekeeperRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('scorekeepers/photos', 'public');
        }

        $scorekeeper = Scorekeeper::create($data);

        return redirect()
            ->route('scorekeepers.show', $scorekeeper)
            ->with('status', "Anotador «{$scorekeeper->full_name}» creado correctamente.");
    }

    public function show(Scorekeeper $scorekeeper): View
    {
        return view('scorekeepers.show', compact('scorekeeper'));
    }

    public function edit(Scorekeeper $scorekeeper): View
    {
        return view('scorekeepers.edit', compact('scorekeeper'));
    }

    public function update(UpdateScorekeeperRequest $request, Scorekeeper $scorekeeper): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $scorekeeper->active);

        if ($request->hasFile('photo')) {
            $this->deletePhoto($scorekeeper);
            $data['photo_path'] = $request->file('photo')->store('scorekeepers/photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($scorekeeper);
            $data['photo_path'] = null;
        }

        $scorekeeper->update($data);

        return redirect()
            ->route('scorekeepers.show', $scorekeeper)
            ->with('status', "Anotador «{$scorekeeper->full_name}» actualizado correctamente.");
    }

    public function destroy(Scorekeeper $scorekeeper): RedirectResponse
    {
        $name = $scorekeeper->full_name;
        $this->deletePhoto($scorekeeper);
        $scorekeeper->delete();

        return redirect()
            ->route('scorekeepers.index')
            ->with('status', "Anotador «{$name}» eliminado correctamente.");
    }

    private function deletePhoto(Scorekeeper $scorekeeper): void
    {
        if ($scorekeeper->photo_path && Storage::disk('public')->exists($scorekeeper->photo_path)) {
            Storage::disk('public')->delete($scorekeeper->photo_path);
        }
    }
}
