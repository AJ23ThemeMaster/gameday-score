<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::orderBy('name')
            ->withCount('games')
            ->paginate(15);

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        $category = Category::create($data);

        return redirect()
            ->route('categories.show', $category)
            ->with('status', "Categoría «{$category->name}» creada correctamente.");
    }

    public function show(Category $category): View
    {
        $category->loadCount('games');

        return view('categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $category->active);
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
