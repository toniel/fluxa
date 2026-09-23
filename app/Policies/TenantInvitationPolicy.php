<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\TenantInvitation;
use App\Models\User;

class TenantInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::MembersInvite->value);
    }

    public function delete(User $user, TenantInvitation $invitation): bool
    {
        return $user->hasPermissionTo(PermissionEnum::MembersRemove->value);
    }
}
