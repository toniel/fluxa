<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::TransactionsCreate->value);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        if ($user->hasPermissionTo(PermissionEnum::TransactionsManage->value)) {
            return true;
        }

        return $user->hasPermissionTo(PermissionEnum::TransactionsManageOwn->value)
            && $transaction->created_by === $user->getKey();
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }
}
