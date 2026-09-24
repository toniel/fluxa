<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class CompleteRegistrationAction
{
    use AsAction;

    /**
     * Lengkapi registrasi user baru: buatkan tenant pribadi, KECUALI bila ia
     * datang lewat undangan (token di session) — ia akan gabung tenant
     * pengundang, bukan dapat tenant sendiri.
     */
    public function handle(User $user): void
    {
        if (session()->has('fluxa.invitation_token')) {
            return;
        }

        app(CreateTenantAction::class)->handle($user, "Keuangan {$user->name}");
    }
}
