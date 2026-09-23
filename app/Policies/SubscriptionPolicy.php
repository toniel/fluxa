<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;

class SubscriptionPolicy
{
    /**
     * Billing hanya untuk pemilik tenant (matriks PRD).
     */
    public function viewAny(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $this->isOwner($user);
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $this->isOwner($user);
    }

    private function isOwner(User $user): bool
    {
        $tenant = app(TenantContext::class)->tenant();

        return $tenant instanceof Tenant
            && $tenant->owner_id === $user->getKey();
    }
}
