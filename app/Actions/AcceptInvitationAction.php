<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvitationStatus;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class AcceptInvitationAction
{
    use AsAction;

    /**
     * Terima undangan: masukkan user ke tenant dengan role undangan.
     *
     * Idempotent: tombol yang diklik dua kali tidak menggandakan pivot.
     * Role dipasang di dalam tenancy tenant itu supaya assignment ter-scope
     * per team seperti alur nyata.
     */
    public function handle(TenantInvitation $invitation, User $user): Tenant
    {
        return DB::transaction(function () use ($invitation, $user): Tenant {
            $tenant = $invitation->tenant;

            $tenant->members()->syncWithoutDetaching([
                $user->getKey() => ['joined_at' => now()],
            ]);

            tenancy()->initialize($tenant);

            try {
                $user->assignRole($invitation->role->value);
            } finally {
                tenancy()->end();
            }

            $invitation->update(['status' => InvitationStatus::Accepted->value]);

            return $tenant;
        });
    }
}
