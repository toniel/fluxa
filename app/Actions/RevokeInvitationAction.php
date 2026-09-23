<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvitationStatus;
use App\Models\TenantInvitation;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class RevokeInvitationAction
{
    use AsAction;

    /**
     * Batalkan undangan: token diacak ulang supaya link yang sudah dikirim
     * benar-benar mati, bukan cuma ditandai.
     */
    public function handle(TenantInvitation $invitation): void
    {
        $invitation->update([
            'status' => InvitationStatus::Revoked->value,
            'token' => Str::random(64),
        ]);
    }
}
