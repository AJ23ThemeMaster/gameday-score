<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRefereeRequest;
use App\Http\Requests\UpdateRefereeRequest;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RefereeController extends Controller
{
    public function index(): View
    {
        $referees = Referee::orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('referees.index', compact('referees'));
    }

    public function create(): View
    {
        return view('referees.create');
    }

    public function store(StoreRefereeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('referees/photos', 'public');
        }

        $referee = Referee::create($data);

        return redirect()
            ->route('referees.show', $referee)
            ->with('status', "Árbitro «{$referee->full_name}» creado correctamente.");
    }

    public function show(Referee $referee): View
    {
        return view('referees.show', compact('referee'));
    }

    public function edit(Referee $referee): View
    {
        return view('referees.edit', compact('referee'));
    }

    public function update(UpdateRefereeRequest $request, Referee $referee): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $referee->active);

        if ($request->hasFile('photo')) {
            $this->deletePhoto($referee);
            $data['photo_path'] = $request->file('photo')->store('referees/photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($referee);
            $data['photo_path'] = null;
        }

        $referee->update($data);

        return redirect()
            ->route('referees.show', $referee)
            ->with('status', "Árbitro «{$referee->full_name}» actualizado correctamente.");
    }

    public function destroy(Referee $referee): RedirectResponse
    {
        $name = $referee->full_name;
        $this->deletePhoto($referee);
        $referee->delete();

        return redirect()
            ->route('referees.index')
            ->with('status', "Árbitro «{$name}» eliminado correctamente.");
    }

    private function deletePhoto(Referee $referee): void
    {
        if ($referee->photo_path && Storage::disk('public')->exists($referee->photo_path)) {
            Storage::disk('public')->delete($referee->photo_path);
        }
    }
}
