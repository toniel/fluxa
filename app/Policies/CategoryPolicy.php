<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Category;
use App\Models\User;

/**
 * Matriks role PRD: kelola kategori hanya untuk owner dan admin.
 *
 * Role dibaca langsung dari permission tensor spatie, yang sudah ter-scope
 * per tenant lewat SyncPermissionTeam (team id = tenant aktif), jadi mengecek
 * tenant di sini hanya akan menambah double-check setelah scope global.
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::CategoriesManage->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::CategoriesManage->value);
    }
}
