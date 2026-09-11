<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('team')
            ->orderBy('name')
            ->withCount('games')
            ->paginate(15);

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        $teams = Team::orderBy('name')->where('active', true)->get();
        $category = new Category();

        return view('categories.create', compact('teams', 'category'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        // Cast defensivo para PHP 8.4 strict types
        $data['team_id'] = $data['team_id'] !== null ? (int) $data['team_id'] : null;

        $category = Category::create($data);

        return redirect()
            ->route('categories.show', $category)
            ->with('status', "Categoría «{$category->name}» creada correctamente.");
    }

    public function show(Category $category): View
    {
        $category->load('team');
        $category->loadCount(['games', 'athletes']);

        return view('categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $teams = Team::orderBy('name')->where('active', true)->get();

        return view('categories.edit', compact('category', 'teams'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $category->active);
        $data['team_id'] = $data['team_id'] !== null ? (int) $data['team_id'] : null;

        $category->update($data);

        return redirect()
            ->route('categories.show', $category)
            ->with('status', "Categoría «{$category->name}» actualizada correctamente.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->games()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', "No se puede eliminar la categoría «{$category->name}» porque tiene juegos asociados.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('status', "Categoría «{$name}» eliminada correctamente.");
    }
}
