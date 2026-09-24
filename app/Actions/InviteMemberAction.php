<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\InvitationFormData;
use App\Enums\InvitationStatus;
use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Notifications\TenantInvitationNotification;
use App\Support\PlanFeatureChecker;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class InviteMemberAction
{
    use AsAction;

    /**
     * Undang email ke tenant, atau kirim ulang bila sudah pernah diundang.
     *
     * Satu email satu baris per tenant: undang ulang menimpa baris lama
     * (token baru membuat link lama mati) dan mengirim notifikasi lagi.
     */
    public function handle(Tenant $tenant, User $inviter, InvitationFormData $data): TenantInvitation
    {
        if (! in_array($data->role, TenantRole::invitable(), true)) {
            throw ValidationException::withMessages([
                'role' => 'Role pemilik tidak bisa diberikan lewat undangan.',
            ]);
        }

        if ($tenant->members()->whereKey($this->userIdFor($data->email))->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah menjadi anggota tenant.',
            ]);
        }

        // Kirim ulang baris yang sudah ada tidak menambah anggota.
        $resend = TenantInvitation::query()
            ->forTenant($tenant)
            ->where('email', Str::lower($data->email))
            ->exists();

        if (! $resend && app(PlanFeatureChecker::class)->atMemberLimit($tenant)) {
            throw ValidationException::withMessages([
                'email' => 'Paket ini mencapai batas anggota. Upgrade untuk mengundang lagi.',
            ]);
        }

        $invitation = TenantInvitation::updateOrCreate(
            ['tenant_id' => $tenant->getKey(), 'email' => Str::lower($data->email)],
            [
                'role' => $data->role->value,
                'token' => Str::random(64),
                'invited_by' => $inviter->getKey(),
                'status' => InvitationStatus::Pending->value,
                'expires_at' => now()->addDays(7),
            ],
        );

        $invitation->notify(new TenantInvitationNotification($invitation));

        return $invitation;
    }

    /**
     * Id user pemilik email itu, atau 0 yang tidak cocok dengan siapa pun
     * supaya pengecekan keanggotaan di atas selalu gagal dengan aman.
     */
    private function userIdFor(string $email): int
    {
        return User::query()->where('email', Str::lower($email))->value('id') ?? 0;
    }
}
