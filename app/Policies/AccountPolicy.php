<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Account;
use App\Models\User;

/**
 * Matriks role PRD: semua role boleh membuat kantong; edit, hapus, dan
 * arsip hanya untuk owner dan admin.
 */
class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Account $account): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::AccountsCreate->value);
    }

    public function update(User $user, Account $account): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Account $account): bool
    {
        return $this->canManage($user);
    }

    public function archive(User $user, Account $account): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::AccountsManage->value);
    }
}
