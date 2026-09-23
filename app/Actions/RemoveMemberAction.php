<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveMemberAction
{
    use AsAction;

    /**
     * Keluarkan anggota (atau keluar sendiri). Policy sudah menolak bila
     * target adalah owner. Role spatie dicabut dalam tenancy tenant itu
     * supaya baris team-nya ikut bersih.
     */
    public function handle(Tenant $tenant, User $target): void
    {
        DB::transaction(function () use ($tenant, $target): void {
            $tenant->members()->detach($target->getKey());

            tenancy()->initialize($tenant);

            try {
                $target->syncRoles([]);
            } finally {
                tenancy()->end();
            }
        });
    }
}
