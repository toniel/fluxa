<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;

class StartFreeSubscriptionAction
{
    use AsAction;

    /**
     * Pastikan tenant punya langganan: buat paket free bila belum ada.
     * Nantinya dipanggil saat tenant dibuat; halaman billing memakainya
     * sebagai jaring pengaman untuk tenant lama.
     */
    public function handle(Tenant $tenant): Subscription
    {
        // tenant_id tidak fillable (konvensi) dan tidak dikirim di payload:
        // hook creating mengisinya dari context, jadi tulis dalam runFor.
        return app(TenantContext::class)->runFor($tenant, function (): Subscription {
            $existing = Subscription::query()->latest('id')->first();

            if ($existing instanceof Subscription) {
                return $existing;
            }

            $free = Plan::query()->bySlug('free') ?? throw new \RuntimeException('Paket free belum di-seed.');

            return Subscription::create([
                'plan_id' => $free->getKey(),
                'status' => SubscriptionStatus::Active->value,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        });
    }
}
