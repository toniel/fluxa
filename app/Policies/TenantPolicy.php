<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TenantPolicy
{
    public function update(User $user, Tenant $tenant): bool
    {
        return $tenant->owner_id === $user->getKey();
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $tenant->owner_id === $user->getKey();
    }

    /**
     * Hanya owner yang boleh mengganti role, dan role owner tidak bisa
     * diberikan lewat sini (butuh transfer ownership eksplisit).
     */
    public function updateRole(User $actor, Tenant $tenant, User $target): Response
    {
        if (! $this->isMember($tenant, $target)) {
            return Response::denyAsNotFound();
        }

        if ($tenant->owner_id !== $actor->getKey()) {
            return Response::deny('Hanya pemilik yang boleh mengubah role.');
        }

        if ($tenant->owner_id === $target->getKey()) {
            return Response::deny('Role pemilik tidak bisa diubah.');
        }

        return Response::allow();
    }

    /**
     * Owner dan admin boleh mengeluarkan, kecuali owner tidak bisa
     * dikeluarkan siapa pun termasuk dirinya sendiri.
     */
    public function removeMember(User $actor, Tenant $tenant, User $target): Response
    {
        if (! $this->isMember($tenant, $target)) {
            return Response::denyAsNotFound();
        }

        if ($tenant->owner_id === $target->getKey()) {
            return Response::deny('Pemilik tidak bisa dikeluarkan dari tenant.');
        }

        return $target->getKey() === $actor->getKey()
            || $actor->hasPermissionTo(PermissionEnum::MembersRemove->value)
            ? Response::allow()
            : Response::deny('Anda tidak berhak mengeluarkan anggota.');
    }

    private function isMember(Tenant $tenant, User $user): bool
    {
        return $tenant->members()->whereKey($user->getKey())->exists();
    }
}
