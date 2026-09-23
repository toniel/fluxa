<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::TransfersCreate->value);
    }

    public function update(User $user, Transfer $transfer): bool
    {
        if ($user->hasPermissionTo(PermissionEnum::TransfersManage->value)) {
            return true;
        }

        return $user->hasPermissionTo(PermissionEnum::TransfersManageOwn->value)
            && $transfer->created_by === $user->getKey();
    }

    public function delete(User $user, Transfer $transfer): bool
    {
        return $this->update($user, $transfer);
    }
}
