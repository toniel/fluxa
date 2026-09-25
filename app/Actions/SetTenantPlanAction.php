<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;

class SetTenantPlanAction
{
    use AsAction;

    /**
     * Set paket tenant ke paket tertentu — dipakai super-admin di /admin.
     *
     * Consuming action yang pakai jalur ini: super-admin mengeset paket
     * eksplisit (free/pro), sementara ToggleSubscriptionAction mendelegasikan
     * ke sini supaya logika menetapkan paket (termasuk penurunan ke free yang
     * mengembalikan subdomain acak) hidup di satu tempat.
     */
    public function handle(Tenant $tenant, Plan $target): Subscription
    {
        // tenant_id tidak fillable (konvensi) dan tidak dikirim di payload:
        // hook creating mengisinya dari context, jadi seluruh baca-tulis
        // berjalan dalam runFor agar benar di HTTP maupun console.
        return app(TenantContext::class)->runFor($tenant, function () use ($tenant, $target): Subscription {
            $subscription = Subscription::query()->latest('id')->first();
            $free = Plan::query()->bySlug('free');

            if ($subscription instanceof Subscription) {
                $subscription->update([
                    'plan_id' => $target->getKey(),
                    'status' => SubscriptionStatus::Active->value,
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                    'cancelled_at' => null,
                ]);

                if ($target->getKey() === $free?->getKey()) {
                    $this->revertToRandomSubdomain($tenant);
                }

                return $subscription->refresh();
            }

            return Subscription::create([
                'plan_id' => $target->getKey(),
                'status' => SubscriptionStatus::Active->value,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        });
    }

    /**
     * Kembalikan alamat utama ke subdomain acak semula (baris tertua).
     * Tanpa custom yang pernah dibuat, tidak ada yang dikerjakan.
     */
    private function revertToRandomSubdomain(Tenant $tenant): void
    {
        $primary = $tenant->domains()->where('is_primary', true)->first();
        $original = $tenant->domains()->orderBy('id')->first();

        if ($primary === null || $original === null || $primary->getKey() === $original->getKey()) {
            return;
        }

        $primary->update(['is_primary' => false, 'redirects_to' => $original->domain]);
        $original->update(['is_primary' => true, 'redirects_to' => null]);
    }
}
