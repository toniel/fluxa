<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateMemberRoleAction
{
    use AsAction;

    /**
     * Ganti role anggota. Policy sudah memastikan pemanggil owner, target
     * anggota biasa, dan role bukan owner.
     */
    public function handle(Tenant $tenant, User $target, TenantRole $role): void
    {
        DB::transaction(function () use ($tenant, $target, $role): void {
            tenancy()->initialize($tenant);

            try {
                $target->syncRoles([$role->value]);
            } finally {
                tenancy()->end();
            }
        });
    }
}
