<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Laravel\Socialite\Contracts\User as GoogleUser;
use Lorisleiva\Actions\Concerns\AsAction;

class FindOrCreateGoogleUserAction
{
    use AsAction;

    /**
     * Cari user dari akun Google, buat bila belum ada.
     *
     * Urutan: google_id dulu, lalu email (link: akun password lama bisa
     * dipakai lewat Google). Mengembalikan [user, baru_dibuat].
     *
     * @return array{User, bool}
     */
    public function handle(GoogleUser $googleUser): array
    {
        $byGoogleId = User::query()->where('google_id', $googleUser->getId())->first();

        if ($byGoogleId instanceof User) {
            $byGoogleId->update(['avatar' => $googleUser->getAvatar()]);

            return [$byGoogleId, false];
        }

        $byEmail = User::query()->where('email', $googleUser->getEmail())->first();

        if ($byEmail instanceof User) {
            $byEmail->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            if ($byEmail->email_verified_at === null) {
                $byEmail->forceFill(['email_verified_at' => now()])->save();
            }

            return [$byEmail, false];
        }

        $user = User::create([
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return [$user, true];
    }
}
