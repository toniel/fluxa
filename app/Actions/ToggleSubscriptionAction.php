<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleSubscriptionAction
{
    use AsAction;

    /**
     * Mock upgrade/downgrade: bolak-balik antara free dan pro-monthly.
     * Berdiri di model nyata supaya halaman, validasi izin, dan pemakaian
     * limit teruji hari ini; webhook Xendit menggantikan isi method ini
     * nanti tanpa mengubah pemanggilnya.
     */
    public function handle(Tenant $tenant): Subscription
    {
        // tenant_id tidak fillable (konvensi) dan tidak dikirim di payload:
        // hook creating mengisinya dari context, jadi seluruh baca-tulis
        // berjalan dalam runFor agar benar di HTTP maupun console.
        return app(TenantContext::class)->runFor($tenant, function (): Subscription {
            $pro = Plan::query()->bySlug('pro-monthly') ?? throw new \RuntimeException('Paket pro-monthly belum di-seed.');
            $free = Plan::query()->bySlug('free') ?? throw new \RuntimeException('Paket free belum di-seed.');

            $subscription = Subscription::query()->latest('id')->first();

            $target = $subscription?->plan_id === $pro->getKey() ? $free : $pro;

            if ($subscription instanceof Subscription) {
                $subscription->update([
                    'plan_id' => $target->getKey(),
                    'status' => SubscriptionStatus::Active->value,
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                    'cancelled_at' => null,
                ]);

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
}
