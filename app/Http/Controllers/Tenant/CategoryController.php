<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\UpsertCategoryAction;
use App\Data\CategoryData;
use App\Data\CategoryFormData;
use App\Enums\CategoryType;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('categories/Index', [
            'categories' => CategoryData::collect(
                Category::query()->orderedForListing()->get(),
            ),
            'can' => [
                'create' => $request->user()->can('create', Category::class),
                'manage' => $this->canManage($request),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Category::class);

        return Inertia::render('categories/Create', [
            'types' => CategoryType::values(),
            'icons' => $this->icons(),
        ]);
    }

    public function store(CategoryFormData $data, UpsertCategoryAction $action): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $action->handle($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori disimpan.']);

        return to_route('categories.index');
    }

    public function edit(Category $category): Response
    {
        Gate::authorize('update', $category);

        return Inertia::render('categories/Edit', [
            'category' => CategoryData::from($category),
            'types' => CategoryType::values(),
            'icons' => $this->icons(),
        ]);
    }

    public function update(CategoryFormData $data, Category $category, UpsertCategoryAction $action): RedirectResponse
    {
        Gate::authorize('update', $category);

        $action->handle($data, $category);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori disimpan.']);

        return to_route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori dihapus.']);

        return to_route('categories.index');
    }

    private function canManage(Request $request): bool
    {
        return $request->user()->can(PermissionEnum::CategoriesManage->value);
    }

    /**
     * @return list<string>
     */
    private function icons(): array
    {
        return config('fluxa.category_icons');
    }
}
